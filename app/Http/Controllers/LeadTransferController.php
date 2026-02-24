<?php

namespace App\Http\Controllers;

use App\Models\LeadTransfer;
use Illuminate\View\View;

class LeadTransferController extends Controller
{
    public function index(): View
    {
        return view('crm.module-index', [
            'title' => 'Předání leadů',
            'description' => 'Evidence předání leadu mezi obchodníky.',
            'rows' => LeadTransfer::query()->latest('transferred_at')->limit(20)->get(['id', 'status', 'transferred_at', 'created_at']),
            'columns' => ['status' => 'Status', 'transferred_at' => 'Předáno', 'created_at' => 'Záznam'],
            'todo' => [
                'Formulář předání leadu',
                'Kdo předal / komu předal',
                'Audit změn stavu leadu',
            ],
        ]);
    }
}
