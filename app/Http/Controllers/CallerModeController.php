<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CallerModeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 401);

        $company = Company::query()
            ->with(['assignedUser', 'firstCaller'])
            ->queuedForCaller($user->id)
            ->orderBy('first_caller_assigned_at')
            ->orderBy('id')
            ->first();

        $activeCall = null;
        if ($company) {
            $activeCall = Call::query()
                ->where('company_id', $company->id)
                ->where('caller_id', $user->id)
                ->where('outcome', 'pending')
                ->latest('called_at')
                ->first();
        }

        $upcomingFollowUps = $user
            ? \App\Models\FollowUp::query()
                ->with('company')
                ->where('assigned_user_id', $user->id)
                ->where('status', 'open')
                ->orderBy('due_at')
                ->limit(5)
                ->get()
            : collect();

        return view('crm.caller-mode.index', [
            'company' => $company,
            'activeCall' => $activeCall,
            'upcomingFollowUps' => $upcomingFollowUps,
        ]);
    }
}
