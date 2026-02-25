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
                'companies' => Company::query()->where('assigned_user_id', $viewedUser->id)->count(),
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
