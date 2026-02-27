<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    private const QUICK_STATUSES = ['open', 'done', 'cancelled'];
    private const COMPANY_STATUSES = ['new', 'follow-up', 'meeting', 'deal', 'lost'];

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
        $this->ensureCanAccessFollowUp(request()->user(), $followUp);

        $followUp->load(['company', 'call', 'assignedUser']);

        return view('crm.follow-ups.show', compact('followUp'));
    }

    public function edit(FollowUp $followUp): View
    {
        $this->ensureCanAccessFollowUp(request()->user(), $followUp);

        return view('crm.follow-ups.form', [
            'followUp' => $followUp,
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'calls' => Call::query()->with('company')->latest('called_at')->limit(100)->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, FollowUp $followUp): RedirectResponse
    {
        $this->ensureCanAccessFollowUp($request->user(), $followUp);

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
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $ids = collect($request->input('follow_up_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()
                ->route('companies.queue.mine')
                ->with('status', 'Nebyly vybrány žádné follow-upy.');
        }

        $query = FollowUp::query()
            ->whereIn('id', $ids)
            ->where('status', '!=', 'done');

        if (! $user->isManager()) {
            $query->where('assigned_user_id', $user->id);
        }

        $updated = $query
            ->update([
                'status' => 'done',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('companies.queue.mine')
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
            'company_status' => ['nullable', 'in:'.implode(',', self::COMPANY_STATUSES)],
            'next_follow_up_at' => ['nullable', 'date'],
            'reassign_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $status = $data['status'];

        if ($status === 'done' && $followUp->company) {
            $this->resolveCompanyAfterFollowUpDone($followUp, $data);
        }

        $followUp->update([
            'status' => $status,
            'completed_at' => $status === 'done'
                ? ($followUp->completed_at ?? now())
                : null,
        ]);

        return redirect()
            ->to(url()->previous() ?: route('companies.queue.mine'))
            ->with('status', 'Stav follow-upu byl rychle upraven.');
    }

    private function resolveCompanyAfterFollowUpDone(FollowUp $followUp, array $data): void
    {
        $company = $followUp->company;
        if (! $company) {
            return;
        }

        $targetStatus = (string) ($data['company_status'] ?? '');
        $nextFollowUpAt = $data['next_follow_up_at'] ?? null;
        $reassignUserId = isset($data['reassign_user_id']) && $data['reassign_user_id'] !== null
            ? (int) $data['reassign_user_id']
            : (int) $followUp->assigned_user_id;

        if ($targetStatus === '') {
            $targetStatus = match ($company->status) {
                'new' => 'follow-up',
                'follow-up' => 'follow-up',
                default => $company->status,
            };
        }

        if ($targetStatus === 'follow-up' && empty($nextFollowUpAt)) {
            throw ValidationException::withMessages([
                'next_follow_up_at' => 'Pokud firma zustava ve follow-upu, je potreba naplanovat dalsi termin.',
            ]);
        }

        $company->update(array_filter([
            'status' => $targetStatus,
            'first_caller_user_id' => $reassignUserId > 0 ? $reassignUserId : null,
            'first_caller_assigned_at' => $reassignUserId > 0 ? now() : null,
        ], fn ($value) => $value !== null));

        if ($targetStatus === 'follow-up' && $nextFollowUpAt) {
            FollowUp::query()->create([
                'company_id' => $company->id,
                'call_id' => $followUp->call_id,
                'assigned_user_id' => $reassignUserId > 0 ? $reassignUserId : $followUp->assigned_user_id,
                'due_at' => $nextFollowUpAt,
                'status' => 'open',
                'note' => 'Navazujici follow-up po uzavreni predchoziho follow-upu.',
            ]);
        }
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

    private function ensureCanAccessFollowUp(?User $user, FollowUp $followUp): void
    {
        if (! $user) {
            abort(401);
        }

        if ($user->isManager()) {
            return;
        }

        abort_unless((int) ($followUp->assigned_user_id ?? 0) === (int) $user->id, 403);
    }
}
