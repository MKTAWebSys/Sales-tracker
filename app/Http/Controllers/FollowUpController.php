<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Company;
use App\Models\FollowUp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(Request $request): View
    {
        $query = FollowUp::query()
            ->with(['company', 'call', 'assignedUser'])
            ->orderBy('due_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('due_from')) {
            $query->whereDate('due_at', '>=', $request->date('due_from')?->toDateString());
        }

        if ($request->filled('due_to')) {
            $query->whereDate('due_at', '<=', $request->date('due_to')?->toDateString());
        }

        return view('crm.follow-ups.index', [
            'followUps' => $query->paginate(20)->withQueryString(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => (string) $request->input('status', ''),
                'company_id' => (string) $request->input('company_id', ''),
                'due_from' => (string) $request->input('due_from', ''),
                'due_to' => (string) $request->input('due_to', ''),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('crm.follow-ups.form', [
            'followUp' => new FollowUp([
                'status' => 'open',
                'due_at' => now()->addDay(),
                'company_id' => $request->integer('company_id') ?: null,
                'call_id' => $request->integer('call_id') ?: null,
            ]),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'calls' => Call::query()->with('company')->latest('called_at')->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateFollowUp($request);
        $data['assigned_user_id'] = $request->user()?->id;
        $data['completed_at'] = ($data['status'] ?? 'open') === 'done' ? now() : null;

        $followUp = FollowUp::create($data);

        return redirect()
            ->route('follow-ups.show', $followUp)
            ->with('status', 'Follow-up created.');
    }

    public function show(FollowUp $followUp): View
    {
        $followUp->load(['company', 'call', 'assignedUser']);

        return view('crm.follow-ups.show', compact('followUp'));
    }

    public function edit(FollowUp $followUp): View
    {
        return view('crm.follow-ups.form', [
            'followUp' => $followUp,
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'calls' => Call::query()->with('company')->latest('called_at')->limit(100)->get(),
        ]);
    }

    public function update(Request $request, FollowUp $followUp): RedirectResponse
    {
        $data = $this->validateFollowUp($request);
        $data['completed_at'] = ($data['status'] ?? 'open') === 'done'
            ? ($followUp->completed_at ?? now())
            : null;

        $followUp->update($data);

        return redirect()
            ->route('follow-ups.show', $followUp)
            ->with('status', 'Follow-up updated.');
    }

    private function validateFollowUp(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'call_id' => ['nullable', 'exists:calls,id'],
            'due_at' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
        ]);
    }
}
