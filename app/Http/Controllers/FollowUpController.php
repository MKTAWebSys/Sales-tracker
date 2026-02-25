<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    private const QUICK_STATUSES = ['open', 'done', 'cancelled'];

    public function index(Request $request): View
    {
        $query = FollowUp::query()
            ->with(['company', 'call', 'assignedUser'])
            ->orderBy('due_at');
        $isManager = $request->user()?->isManager() ?? false;
        $mine = $isManager
            ? (string) $request->input('mine', $request->filled('assigned_user_id') ? '0' : '1')
            : '1';

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($mine === '1' && $request->user()) {
            $query->where('assigned_user_id', $request->user()->id);
        } elseif ($isManager && $request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->integer('assigned_user_id'));
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
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => (string) $request->input('status', ''),
                'company_id' => (string) $request->input('company_id', ''),
                'assigned_user_id' => (string) $request->input('assigned_user_id', ''),
                'mine' => $mine,
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
                'assigned_user_id' => $request->user()?->id,
            ]),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'calls' => Call::query()->with('company')->latest('called_at')->limit(100)->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateFollowUp($request);
        if ($request->user()?->isManager()) {
            $data['assigned_user_id'] = $data['assigned_user_id'] ?? $request->user()?->id;
        } else {
            $data['assigned_user_id'] = $request->user()?->id;
        }
        $data['completed_at'] = ($data['status'] ?? 'open') === 'done' ? now() : null;

        $followUp = FollowUp::create($data);

        return redirect()
            ->route('follow-ups.show', $followUp)
            ->with('status', 'Follow-up byl vytvořen.');
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
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, FollowUp $followUp): RedirectResponse
    {
        $data = $this->validateFollowUp($request);
        if (! $request->user()?->isManager()) {
            $data['assigned_user_id'] = $followUp->assigned_user_id;
        }
        $data['completed_at'] = ($data['status'] ?? 'open') === 'done'
            ? ($followUp->completed_at ?? now())
            : null;

        $followUp->update($data);

        return redirect()
            ->route('follow-ups.show', $followUp)
            ->with('status', 'Follow-up byl upraven.');
    }

    public function bulkComplete(Request $request): RedirectResponse
    {
        $ids = collect($request->input('follow_up_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()
                ->route('follow-ups.index', $request->except('_token'))
                ->with('status', 'Nebyly vybrány žádné follow-upy.');
        }

        $updated = FollowUp::query()
            ->whereIn('id', $ids)
            ->where('status', '!=', 'done')
            ->update([
                'status' => 'done',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('follow-ups.index', $request->except('_token', 'follow_up_ids'))
            ->with('status', "Označeno jako hotové: {$updated} follow-upů.");
    }

    public function quickStatus(Request $request, FollowUp $followUp): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isManager() && $followUp->assigned_user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::QUICK_STATUSES)],
        ]);

        $status = $data['status'];
        $followUp->update([
            'status' => $status,
            'completed_at' => $status === 'done'
                ? ($followUp->completed_at ?? now())
                : null,
        ]);

        return redirect()
            ->to(url()->previous() ?: route('follow-ups.index'))
            ->with('status', 'Stav follow-upu byl rychle upraven.');
    }

    private function validateFollowUp(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'call_id' => ['nullable', 'exists:calls,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'due_at' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
        ]);
    }
}
