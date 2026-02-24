<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CallController extends Controller
{
    public function index(): View
    {
        return view('crm.calls.index', [
            'calls' => Call::query()
                ->with(['company', 'caller'])
                ->latest('called_at')
                ->paginate(20),
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
