<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(): View
    {
        return view('crm.module-index', [
            'title' => 'Follow-upy',
            'description' => 'Navazující úkoly po hovorech a jejich termíny.',
            'rows' => FollowUp::query()->orderBy('due_at')->limit(20)->get(['id', 'status', 'due_at', 'completed_at']),
            'columns' => ['status' => 'Status', 'due_at' => 'Termín', 'completed_at' => 'Dokončeno'],
            'todo' => [
                'CRUD follow-upu',
                'Přiřazení obchodníkovi',
                'Připomínky a notifikace',
            ],
        ]);
    }
}
