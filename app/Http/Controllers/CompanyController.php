<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Company::query()->with('assignedUser')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->integer('assigned_user_id'));
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('ico', 'like', "%{$search}%");
            });
        }

        return view('crm.companies.index', [
            'companies' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'q' => (string) $request->input('q', ''),
                'status' => (string) $request->input('status', ''),
                'assigned_user_id' => (string) $request->input('assigned_user_id', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('crm.companies.form', [
            'company' => new Company(['status' => 'new']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ico' => ['nullable', 'string', 'max:32'],
            'website' => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['assigned_user_id'] = $request->user()?->id;

        $company = Company::create($data);

        return redirect()
            ->route('companies.show', $company)
            ->with('status', 'Company created.');
    }

    public function show(Company $company): View
    {
        $company->load([
            'assignedUser',
            'calls' => fn ($query) => $query->latest('called_at')->limit(10),
        ]);

        return view('crm.companies.show', compact('company'));
    }

    public function edit(Company $company): View
    {
        return view('crm.companies.form', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ico' => ['nullable', 'string', 'max:32'],
            'website' => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $company->update($data);

        return redirect()
            ->route('companies.show', $company)
            ->with('status', 'Company updated.');
    }
}
