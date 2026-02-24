<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\LeadTransfer;
use App\Models\Meeting;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'stats' => [
                'companies' => Company::count(),
                'calls' => Call::count(),
                'followUpsOpen' => FollowUp::query()->where('status', 'open')->count(),
                'leadTransfers' => LeadTransfer::count(),
                'meetingsPlanned' => Meeting::query()->whereIn('status', ['planned', 'confirmed'])->count(),
            ],
        ]);
    }
}
