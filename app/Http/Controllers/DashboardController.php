<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\LeadTransfer;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $user = $request->user();
        $isManager = $user?->isManager() ?? false;

        $viewedUser = $user;
        if ($isManager && $request->filled('user_view_id')) {
            $selectedUser = User::query()->find($request->integer('user_view_id'));
            if ($selectedUser) {
                $viewedUser = $selectedUser;
            }
        }

        $isViewingOtherUser = $isManager
            && $user
            && $viewedUser
            && $user->id !== $viewedUser->id;

        $dueTodayQuery = FollowUp::query()
            ->with(['company', 'assignedUser'])
            ->where('status', 'open')
            ->whereDate('due_at', $today);

        $overdueQuery = FollowUp::query()
            ->with(['company', 'assignedUser'])
            ->where('status', 'open')
            ->whereDate('due_at', '<', $today);

        if ($viewedUser) {
            $dueTodayQuery->where('assigned_user_id', $viewedUser->id);
            $overdueQuery->where('assigned_user_id', $viewedUser->id);
        }

        $dashboardQueueOverdue = collect();
        $dashboardQueueToday = collect();
        $dashboardQueueFuture = collect();

        if ($viewedUser) {
            $queueFeed = collect();

            $queueCompanies = Company::query()
                ->with(['assignedUser', 'firstCaller'])
                ->queuedForCaller($viewedUser->id)
                ->orderBy('first_caller_assigned_at')
                ->orderBy('id')
                ->limit(12)
                ->get();

            $queueFollowUps = FollowUp::query()
                ->with('company')
                ->where('assigned_user_id', $viewedUser->id)
                ->where('status', 'open')
                ->orderBy('due_at')
                ->limit(12)
                ->get();

            $queueMeetings = Meeting::query()
                ->with('company')
                ->whereIn('status', ['planned', 'confirmed'])
                ->whereHas('call', fn ($query) => $query->where('caller_id', $viewedUser->id))
                ->orderBy('scheduled_at')
                ->limit(12)
                ->get();

            foreach ($queueCompanies as $company) {
                $queueFeed->push([
                    'type' => 'company',
                    'at' => $company->first_caller_assigned_at ?? $company->created_at,
                    'sort_ts' => optional($company->first_caller_assigned_at ?? $company->created_at)?->getTimestamp() ?? 0,
                    'company' => $company,
                ]);
            }

            foreach ($queueFollowUps as $followUp) {
                $queueFeed->push([
                    'type' => 'follow-up',
                    'at' => $followUp->due_at,
                    'sort_ts' => optional($followUp->due_at)?->getTimestamp() ?? 0,
                    'followUp' => $followUp,
                    'company' => $followUp->company,
                ]);
            }

            foreach ($queueMeetings as $meeting) {
                $queueFeed->push([
                    'type' => 'meeting',
                    'at' => $meeting->scheduled_at,
                    'sort_ts' => optional($meeting->scheduled_at)?->getTimestamp() ?? 0,
                    'meeting' => $meeting,
                    'company' => $meeting->company,
                ]);
            }

            $queueFeed = $queueFeed
                ->sortBy([
                    ['sort_ts', 'asc'],
                    ['type', 'asc'],
                ])
                ->values();

            $startOfToday = now()->startOfDay();
            $endOfToday = now()->endOfDay();

            $dashboardQueueOverdue = $queueFeed
                ->filter(fn ($item) => ($item['type'] ?? '') !== 'company')
                ->filter(fn ($item) => $item['at'] && $item['at']->lt($startOfToday))
                ->values();
            $dashboardQueueToday = $queueFeed
                ->filter(fn ($item) => ($item['type'] ?? '') === 'company'
                    || ! $item['at']
                    || ($item['at']->gte($startOfToday) && $item['at']->lte($endOfToday)))
                ->values();
            $dashboardQueueFuture = $queueFeed
                ->filter(fn ($item) => ($item['type'] ?? '') !== 'company')
                ->filter(fn ($item) => $item['at'] && $item['at']->gt($endOfToday))
                ->values();
        }

        $performanceView = in_array((string) $request->input('perf_view', 'month'), ['day', 'week', 'month'], true)
            ? (string) $request->input('perf_view', 'month')
            : 'month';
        $performancePreset = in_array((string) $request->input('perf_preset', ''), ['this_month', 'last_month', 'q1', 'ytd'], true)
            ? (string) $request->input('perf_preset', '')
            : '';
        $performanceAnchorDate = $request->date('perf_date')
            ? $request->date('perf_date')->startOfDay()
            : now()->startOfDay();

        if ($request->filled('perf_from') || $request->filled('perf_to')) {
            $performanceFrom = $request->date('perf_from')
                ? $request->date('perf_from')->startOfDay()
                : now()->startOfMonth();
            $performanceTo = $request->date('perf_to')
                ? $request->date('perf_to')->endOfDay()
                : now()->endOfMonth();
        } elseif ($performancePreset !== '') {
            [$performanceFrom, $performanceTo, $performanceView, $performanceAnchorDate] = match ($performancePreset) {
                'this_month' => [
                    now()->startOfMonth(),
                    now()->endOfMonth(),
                    'month',
                    now()->startOfDay(),
                ],
                'last_month' => [
                    now()->subMonthNoOverflow()->startOfMonth(),
                    now()->subMonthNoOverflow()->endOfMonth(),
                    'month',
                    now()->subMonthNoOverflow()->startOfDay(),
                ],
                'q1' => [
                    now()->startOfYear(),
                    now()->startOfYear()->addMonths(2)->endOfMonth(),
                    'month',
                    now()->startOfYear(),
                ],
                default => [
                    now()->startOfYear(),
                    now()->endOfDay(),
                    'month',
                    now()->startOfDay(),
                ],
            };
        } else {
            [$performanceFrom, $performanceTo] = match ($performanceView) {
                'day' => [
                    $performanceAnchorDate->copy()->startOfDay(),
                    $performanceAnchorDate->copy()->endOfDay(),
                ],
                'week' => [
                    $performanceAnchorDate->copy()->startOfWeek(Carbon::MONDAY),
                    $performanceAnchorDate->copy()->endOfWeek(Carbon::MONDAY),
                ],
                default => [
                    $performanceAnchorDate->copy()->startOfMonth(),
                    $performanceAnchorDate->copy()->endOfMonth(),
                ],
            };
        }

        if ($performanceFrom->gt($performanceTo)) {
            [$performanceFrom, $performanceTo] = [$performanceTo->copy()->startOfDay(), $performanceFrom->copy()->endOfDay()];
        }

        $firstCallIdsSub = Call::query()
            ->selectRaw('MIN(id) as first_call_id, company_id')
            ->groupBy('company_id');

        $firstCallsSub = Call::query()
            ->from('calls as c')
            ->joinSub($firstCallIdsSub, 'first_call_ids', function ($join) {
                $join->on('c.id', '=', 'first_call_ids.first_call_id');
            })
            ->whereBetween('c.called_at', [$performanceFrom, $performanceTo])
            ->selectRaw('c.caller_id, c.company_id');

        $userPerformance = User::query()
            ->select('users.id', 'users.name')
            ->selectRaw('COUNT(DISTINCT first_calls.company_id) as new_called_companies')
            ->selectRaw('COUNT(DISTINCT CASE WHEN meetings.id IS NOT NULL THEN first_calls.company_id END) as meeting_companies')
            ->selectRaw("COUNT(DISTINCT CASE WHEN companies.status = 'deal' THEN first_calls.company_id END) as deal_companies")
            ->leftJoinSub($firstCallsSub, 'first_calls', function ($join) {
                $join->on('first_calls.caller_id', '=', 'users.id');
            })
            ->leftJoin('meetings', 'meetings.company_id', '=', 'first_calls.company_id')
            ->leftJoin('companies', 'companies.id', '=', 'first_calls.company_id')
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->get()
            ->map(function ($row) {
                $called = (int) ($row->new_called_companies ?? 0);
                $meetings = (int) ($row->meeting_companies ?? 0);
                $deals = (int) ($row->deal_companies ?? 0);

                $row->meeting_rate = $called > 0 ? round(($meetings / $called) * 100, 1) : 0.0;
                $row->deal_rate = $called > 0 ? round(($deals / $called) * 100, 1) : 0.0;

                return $row;
            });

        $viewedUserPerformance = $viewedUser
            ? $userPerformance->firstWhere('id', $viewedUser->id)
            : null;

        $monthlyTargetProgress = null;
        if ($viewedUser) {
            $monthFrom = now()->startOfMonth();
            $monthTo = now()->endOfMonth();

            $monthCalledNewCompanies = Call::query()
                ->from('calls as c')
                ->joinSub($firstCallIdsSub, 'first_call_ids', function ($join) {
                    $join->on('c.id', '=', 'first_call_ids.first_call_id');
                })
                ->where('c.caller_id', $viewedUser->id)
                ->whereBetween('c.called_at', [$monthFrom, $monthTo])
                ->count();

            $targetCount = $viewedUser->call_target_count;
            $remaining = $targetCount ? max((int) $targetCount - (int) $monthCalledNewCompanies, 0) : null;

            $monthlyTargetProgress = [
                'from' => $monthFrom,
                'to' => $monthTo,
                'called' => (int) $monthCalledNewCompanies,
                'target' => $targetCount ? (int) $targetCount : null,
                'remaining' => $remaining,
                'ratio' => $targetCount ? round(((int) $monthCalledNewCompanies / max((int) $targetCount, 1)) * 100, 1) : null,
            ];
        }

        return view('dashboard', [
            'stats' => [
                'companies' => Company::count(),
                'calls' => Call::count(),
                'followUpsOpen' => FollowUp::query()->where('status', 'open')->count(),
                'followUpsDueToday' => FollowUp::query()->where('status', 'open')->whereDate('due_at', $today)->count(),
                'followUpsOverdue' => FollowUp::query()->where('status', 'open')->whereDate('due_at', '<', $today)->count(),
                'leadTransfers' => LeadTransfer::count(),
                'meetingsPlanned' => Meeting::query()->where('status', 'planned')->count(),
            ],
            'dashboardUsers' => $isManager ? User::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'viewedUser' => $viewedUser,
            'isViewingOtherUser' => $isViewingOtherUser,
            'myStats' => $viewedUser ? [
                'queueCompanies' => Company::query()
                    ->where('first_caller_user_id', $viewedUser->id)
                    ->where('status', 'new')
                    ->whereNull('first_contacted_at')
                    ->count(),
                'ownerCompanies' => Company::query()->where('assigned_user_id', $viewedUser->id)->count(),
                'followUpsOpen' => FollowUp::query()->where('assigned_user_id', $viewedUser->id)->where('status', 'open')->count(),
                'followUpsOverdue' => FollowUp::query()->where('assigned_user_id', $viewedUser->id)->where('status', 'open')->whereDate('due_at', '<', $today)->count(),
            ] : null,
            'followUpsDueTodayList' => $dueTodayQuery
                ->orderBy('due_at')
                ->limit(8)
                ->get(),
            'followUpsOverdueList' => $overdueQuery
                ->orderBy('due_at')
                ->limit(8)
                ->get(),
            'myFollowUpsList' => $viewedUser
                ? FollowUp::query()
                    ->with(['company'])
                    ->where('assigned_user_id', $viewedUser->id)
                    ->where('status', 'open')
                    ->orderBy('due_at')
                    ->limit(8)
                    ->get()
                : collect(),
            'userPerformance' => $userPerformance,
            'viewedUserPerformance' => $viewedUserPerformance,
            'performancePeriod' => [
                'from' => $performanceFrom,
                'to' => $performanceTo,
            ],
            'performanceView' => $performanceView,
            'performanceAnchorDate' => $performanceAnchorDate,
            'performancePreset' => $performancePreset,
            'dashboardQueueOverdue' => $dashboardQueueOverdue,
            'dashboardQueueToday' => $dashboardQueueToday,
            'dashboardQueueFuture' => $dashboardQueueFuture,
            'monthlyTargetProgress' => $monthlyTargetProgress,
        ]);
    }

    public function updateUserTarget(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $authUser = $request->user();
        abort_unless($authUser?->isManager(), 403);

        $data = $request->validate([
            'call_target_count' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'call_target_until' => ['nullable', 'date'],
            'clear_target' => ['nullable', 'boolean'],
        ]);

        $clearTarget = (bool) ($data['clear_target'] ?? false);
        if ($clearTarget) {
            $user->update([
                'call_target_count' => null,
                'call_target_until' => null,
            ]);
        } else {
            $user->update([
                'call_target_count' => $data['call_target_count'] ?? null,
                'call_target_until' => $data['call_target_until'] ?? null,
            ]);
        }

        return redirect()
            ->route('dashboard', ['user_view_id' => $user->id])
            ->with('status', 'Cil obvolani byl ulozen.');
    }
}
