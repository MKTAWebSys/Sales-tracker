<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CallController extends Controller
{
    public function index(Request $request): View
    {
        $query = Call::query()
            ->with(['company', 'caller'])
            ->latest('called_at');

        if ($request->filled('outcome')) {
            $query->where('outcome', $request->string('outcome'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('caller_id')) {
            $query->where('caller_id', $request->integer('caller_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('called_at', '>=', $request->date('date_from')?->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('called_at', '<=', $request->date('date_to')?->toDateString());
        }

        return view('crm.calls.index', [
            'calls' => $query->paginate(20)->withQueryString(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'outcome' => (string) $request->input('outcome', ''),
                'company_id' => (string) $request->input('company_id', ''),
                'caller_id' => (string) $request->input('caller_id', ''),
                'date_from' => (string) $request->input('date_from', ''),
                'date_to' => (string) $request->input('date_to', ''),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('crm.calls.form', [
            'call' => new Call([
                'called_at' => now(),
                'outcome' => 'callback',
                'company_id' => $request->integer('company_id') ?: null,
            ]),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCall($request);
        $data['caller_id'] = $request->user()?->id;

        $call = Call::create($data);

        return redirect()
            ->route('calls.show', $call)
            ->with('status', 'Call saved.');
    }

    public function show(Call $call): View
    {
        $call->load(['company', 'caller', 'handedOverTo']);

        return view('crm.calls.show', compact('call'));
    }

    public function edit(Call $call): View
    {
        return view('crm.calls.form', [
            'call' => $call,
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Call $call): RedirectResponse
    {
        $data = $this->validateCall($request);
        $call->update($data);

        return redirect()
            ->route('calls.show', $call)
            ->with('status', 'Call updated.');
    }

    private function validateCall(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'called_at' => ['required', 'date'],
            'outcome' => ['required', 'string', 'max:50'],
            'summary' => ['nullable', 'string'],
            'next_follow_up_at' => ['nullable', 'date'],
            'meeting_planned_at' => ['nullable', 'date'],
            'handed_over_to_id' => ['nullable', 'exists:users,id'],
        ]);
    }
}
