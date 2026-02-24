<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function index(): View
    {
        return view('crm.module-index', [
            'title' => 'Schůzky',
            'description' => 'Schůzky, obchodní jednání a jejich stav.',
            'rows' => Meeting::query()->orderBy('scheduled_at')->limit(20)->get(['id', 'mode', 'status', 'scheduled_at']),
            'columns' => ['mode' => 'Forma', 'status' => 'Status', 'scheduled_at' => 'Termín'],
            'todo' => [
                'Formulář schůzky',
                'Napojení na call a firmu',
                'Základní stav obchodu',
            ],
        ]);
    }
}
