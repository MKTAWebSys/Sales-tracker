<?php

namespace App\Http\Controllers;

use App\Models\Call;
use Illuminate\View\View;

class CallController extends Controller
{
    public function index(): View
    {
        return view('crm.module-index', [
            'title' => 'Hovory',
            'description' => 'Historie callů, výsledky a plánování dalšího kontaktu.',
            'rows' => Call::query()->latest('called_at')->limit(20)->get(['id', 'outcome', 'called_at', 'next_follow_up_at']),
            'columns' => ['outcome' => 'Výsledek', 'called_at' => 'Voláno', 'next_follow_up_at' => 'Další follow-up'],
            'todo' => [
                'Formulář záznamu hovoru',
                'Vazba na firmu a volajícího',
                'Generování follow-upu ze záznamu',
            ],
        ]);
    }
}
