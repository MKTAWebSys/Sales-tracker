<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user?->isManager() ?? false;
        $date = $request->date('date')?->startOfDay() ?? now()->startOfDay();
        $viewMode = in_array((string) $request->input('view', 'week'), ['day', 'week', 'month'], true)
            ? (string) $request->input('view', 'week')
            : 'week';
        $mine = $isManager ? (string) $request->input('mine', '1') : '1';

        [$rangeStart, $rangeEnd] = $this->resolveRange($date, $viewMode);

        $selectedFollowUpsQuery = FollowUp::query()
            ->with(['company', 'assignedUser'])
            ->where('status', 'open')
            ->whereDate('due_at', $date->toDateString());
        $selectedMeetingsQuery = Meeting::query()
            ->with(['company'])
            ->whereIn('status', ['planned', 'confirmed'])
            ->whereDate('scheduled_at', $date->toDateString());

        $rangeFollowUpsQuery = FollowUp::query()
            ->with(['company', 'assignedUser'])
            ->where('status', 'open')
            ->whereBetween('due_at', [$rangeStart, $rangeEnd]);
        $rangeMeetingsQuery = Meeting::query()
            ->with(['company'])
            ->whereIn('status', ['planned', 'confirmed'])
            ->whereBetween('scheduled_at', [$rangeStart, $rangeEnd]);

        $this->applyAgendaFilters($selectedFollowUpsQuery, $selectedMeetingsQuery, $request, $user, $isManager, $mine);
        $this->applyAgendaFilters($rangeFollowUpsQuery, $rangeMeetingsQuery, $request, $user, $isManager, $mine);

        $followUps = $selectedFollowUpsQuery->orderBy('due_at')->get();
        $meetings = $selectedMeetingsQuery->orderBy('scheduled_at')->get();
        $rangeFollowUps = $rangeFollowUpsQuery->orderBy('due_at')->get();
        $rangeMeetings = $rangeMeetingsQuery->orderBy('scheduled_at')->get();

        $items = $this->buildTimelineItems($followUps, $meetings);
        $dayCounts = $this->buildDayCounts($rangeFollowUps, $rangeMeetings);
        $calendarGrid = $this->buildCalendarGrid($date, $viewMode, $dayCounts);

        return view('crm.calendar.index', [
            'calendarDate' => $date,
            'viewMode' => $viewMode,
            'items' => $items,
            'isManager' => $isManager,
            'filters' => [
                'mine' => $mine,
                'assigned_user_id' => (string) $request->input('assigned_user_id', ''),
                'view' => $viewMode,
            ],
            'users' => $isManager ? User::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'counts' => [
                'followUps' => $followUps->count(),
                'meetings' => $meetings->count(),
                'total' => $items->count(),
            ],
            'rangeCounts' => [
                'followUps' => $rangeFollowUps->count(),
                'meetings' => $rangeMeetings->count(),
                'total' => $rangeFollowUps->count() + $rangeMeetings->count(),
            ],
            'calendarGrid' => $calendarGrid,
        ]);
    }

    private function applyAgendaFilters($followUpsQuery, $meetingsQuery, Request $request, $user, bool $isManager, string $mine): void
    {
        if ($mine === '1' && $user) {
            $followUpsQuery->where('assigned_user_id', $user->id);
            $meetingsQuery->whereHas('company', function ($query) use ($user) {
                $query->where(function ($subQuery) use ($user) {
                    $subQuery
                        ->where('assigned_user_id', $user->id)
                        ->orWhere('first_caller_user_id', $user->id);
                });
            });

            return;
        }

        if ($isManager && $request->filled('assigned_user_id')) {
            $assignedUserId = $request->integer('assigned_user_id');
            $followUpsQuery->where('assigned_user_id', $assignedUserId);
            $meetingsQuery->whereHas('company', function ($query) use ($assignedUserId) {
                $query->where(function ($subQuery) use ($assignedUserId) {
                    $subQuery
                        ->where('assigned_user_id', $assignedUserId)
                        ->orWhere('first_caller_user_id', $assignedUserId);
                });
            });
        }
    }

    private function resolveRange(Carbon $date, string $viewMode): array
    {
        return match ($viewMode) {
            'day' => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
            'week' => [$date->copy()->startOfWeek(Carbon::MONDAY), $date->copy()->endOfWeek(Carbon::SUNDAY)],
            'month' => [
                $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY),
                $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY),
            ],
            default => [$date->copy()->startOfWeek(Carbon::MONDAY), $date->copy()->endOfWeek(Carbon::SUNDAY)],
        };
    }

    private function buildDayCounts(Collection $followUps, Collection $meetings): array
    {
        $counts = [];

        foreach ($followUps as $followUp) {
            $key = $followUp->due_at?->toDateString();
            if (! $key) {
                continue;
            }

            $counts[$key] = $counts[$key] ?? ['total' => 0, 'followUps' => 0, 'meetings' => 0];
            $counts[$key]['total']++;
            $counts[$key]['followUps']++;
        }

        foreach ($meetings as $meeting) {
            $key = $meeting->scheduled_at?->toDateString();
            if (! $key) {
                continue;
            }

            $counts[$key] = $counts[$key] ?? ['total' => 0, 'followUps' => 0, 'meetings' => 0];
            $counts[$key]['total']++;
            $counts[$key]['meetings']++;
        }

        return $counts;
    }

    private function buildCalendarGrid(Carbon $date, string $viewMode, array $dayCounts): array
    {
        if ($viewMode === 'day') {
            $key = $date->toDateString();
            return [
                'type' => 'day',
                'days' => [[
                    'date' => $date->copy(),
                    'key' => $key,
                    'isCurrentMonth' => true,
                    'isSelected' => true,
                    'isToday' => $date->isSameDay(now()),
                    'counts' => $dayCounts[$key] ?? ['total' => 0, 'followUps' => 0, 'meetings' => 0],
                ]],
            ];
        }

        if ($viewMode === 'week') {
            $start = $date->copy()->startOfWeek(Carbon::MONDAY);
            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $day = $start->copy()->addDays($i);
                $key = $day->toDateString();
                $days[] = [
                    'date' => $day,
                    'key' => $key,
                    'isCurrentMonth' => true,
                    'isSelected' => $day->isSameDay($date),
                    'isToday' => $day->isSameDay(now()),
                    'counts' => $dayCounts[$key] ?? ['total' => 0, 'followUps' => 0, 'meetings' => 0],
                ];
            }

            return ['type' => 'week', 'days' => $days];
        }

        $start = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $rows = [];
        for ($week = 0; $week < 6; $week++) {
            $row = [];
            for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
                $day = $start->copy()->addDays(($week * 7) + $dayIndex);
                $key = $day->toDateString();
                $row[] = [
                    'date' => $day,
                    'key' => $key,
                    'isCurrentMonth' => $day->month === $date->month,
                    'isSelected' => $day->isSameDay($date),
                    'isToday' => $day->isSameDay(now()),
                    'counts' => $dayCounts[$key] ?? ['total' => 0, 'followUps' => 0, 'meetings' => 0],
                ];
            }
            $rows[] = $row;
        }

        return ['type' => 'month', 'rows' => $rows];
    }

    private function buildTimelineItems(Collection $followUps, Collection $meetings): Collection
    {
        return collect()
            ->merge($followUps->map(function (FollowUp $followUp) {
                return [
                    'type' => 'follow-up',
                    'at' => $followUp->due_at,
                    'title' => $followUp->company?->name ?? 'Bez firmy',
                    'subtitle' => $followUp->assignedUser?->name ? 'Prirazeno: '.$followUp->assignedUser->name : 'Neprirazeno',
                    'note' => $followUp->note,
                    'status' => $followUp->status,
                    'model' => $followUp,
                    'detail_url' => route('follow-ups.show', $followUp),
                ];
            }))
            ->merge($meetings->map(function (Meeting $meeting) {
                return [
                    'type' => 'meeting',
                    'at' => $meeting->scheduled_at,
                    'title' => $meeting->company?->name ?? 'Bez firmy',
                    'subtitle' => 'Forma: '.$meeting->mode,
                    'note' => $meeting->note,
                    'status' => $meeting->status,
                    'model' => $meeting,
                    'detail_url' => route('meetings.show', $meeting),
                ];
            }))
            ->sortBy(fn ($item) => ($item['at']?->getTimestamp() ?? 0).'-'.Str::lower($item['type']))
            ->values();
    }
}
