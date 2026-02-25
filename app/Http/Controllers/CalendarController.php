<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user?->isManager() ?? false;
        $date = $request->date('date')?->startOfDay() ?? now()->startOfDay();
        $mine = $isManager ? (string) $request->input('mine', '1') : '1';

        $followUpsQuery = FollowUp::query()
            ->with(['company', 'assignedUser'])
            ->where('status', 'open')
            ->whereDate('due_at', $date->toDateString());

        if ($mine === '1' && $user) {
            $followUpsQuery->where('assigned_user_id', $user->id);
        } elseif ($isManager && $request->filled('assigned_user_id')) {
            $followUpsQuery->where('assigned_user_id', $request->integer('assigned_user_id'));
        }

        $meetingsQuery = Meeting::query()
            ->with(['company'])
            ->whereIn('status', ['planned', 'confirmed'])
            ->whereDate('scheduled_at', $date->toDateString());

        if ($mine === '1' && $user) {
            $meetingsQuery->whereHas('company', function ($query) use ($user) {
                $query->where(function ($subQuery) use ($user) {
                    $subQuery
                        ->where('assigned_user_id', $user->id)
                        ->orWhere('first_caller_user_id', $user->id);
                });
            });
        } elseif ($isManager && $request->filled('assigned_user_id')) {
            $assignedUserId = $request->integer('assigned_user_id');
            $meetingsQuery->whereHas('company', function ($query) use ($assignedUserId) {
                $query->where(function ($subQuery) use ($assignedUserId) {
                    $subQuery
                        ->where('assigned_user_id', $assignedUserId)
                        ->orWhere('first_caller_user_id', $assignedUserId);
                });
            });
        }

        $followUps = $followUpsQuery->orderBy('due_at')->get();
        $meetings = $meetingsQuery->orderBy('scheduled_at')->get();

        $items = $this->buildTimelineItems($followUps, $meetings);

        return view('crm.calendar.index', [
            'calendarDate' => $date,
            'items' => $items,
            'isManager' => $isManager,
            'filters' => [
                'mine' => $mine,
                'assigned_user_id' => (string) $request->input('assigned_user_id', ''),
            ],
            'users' => $isManager ? User::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'counts' => [
                'followUps' => $followUps->count(),
                'meetings' => $meetings->count(),
                'total' => $items->count(),
            ],
        ]);
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
            ->sortBy([
                ['at', 'asc'],
                ['type', 'asc'],
            ])
            ->values();
    }
}
