<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        return view('crm.module-index', [
            'title' => 'Firmy',
            'description' => 'Evidence firem a základních obchodních informací.',
            'rows' => Company::query()->latest()->limit(20)->get(['id', 'name', 'status', 'created_at']),
            'columns' => ['name' => 'Název', 'status' => 'Status', 'created_at' => 'Vytvořeno'],
            'todo' => [
                'Formulář firmy (IČO, web, poznámky, owner)',
                'Detail firmy s historií hovorů',
                'Filtrování a fulltext',
            ],
        ]);
    }
}
