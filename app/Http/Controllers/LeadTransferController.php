<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Company;
use App\Models\LeadTransfer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadTransferController extends Controller
{
    private const QUICK_STATUSES = ['pending', 'accepted', 'done', 'cancelled'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = LeadTransfer::query()
            ->with(['company', 'call', 'fromUser', 'toUser'])
            ->latest('transferred_at');

        if ($user && ! $user->isManager()) {
            $query->where(function ($subQuery) use ($user) {
                $subQuery
                    ->where('from_user_id', $user->id)
                    ->orWhere('to_user_id', $user->id)
                    ->orWhereHas('company', fn ($q) => $q
                        ->where('assigned_user_id', $user->id)
                        ->orWhere('first_caller_user_id', $user->id));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('call_id')) {
            $query->where('call_id', $request->integer('call_id'));
        }

        if ($request->filled('from_user_id')) {
            $query->where('from_user_id', $request->integer('from_user_id'));
        }

        if ($request->filled('to_user_id')) {
            $query->where('to_user_id', $request->integer('to_user_id'));
        }

        return view('crm.lead-transfers.index', [
            'leadTransfers' => $query->paginate(20)->withQueryString(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => (string) $request->input('status', ''),
                'company_id' => (string) $request->input('company_id', ''),
                'call_id' => (string) $request->input('call_id', ''),
                'from_user_id' => (string) $request->input('from_user_id', ''),
                'to_user_id' => (string) $request->input('to_user_id', ''),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('crm.lead-transfers.form', [
            'leadTransfer' => new LeadTransfer([
                'status' => 'pending',
                'transferred_at' => now(),
                'company_id' => $request->integer('company_id') ?: null,
                'call_id' => $request->integer('call_id') ?: null,
                'from_user_id' => $request->user()?->id,
            ]),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'calls' => Call::query()->with('company')->latest('called_at')->limit(100)->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $leadTransfer = LeadTransfer::create($this->validateLeadTransfer($request));

        return redirect()
            ->route('lead-transfers.show', $leadTransfer)
            ->with('status', 'Předání leadu bylo vytvořeno.');
    }

    public function show(LeadTransfer $leadTransfer): View
    {
        $this->ensureCanAccessLeadTransfer(request()->user(), $leadTransfer);

        $leadTransfer->load(['company', 'call.company', 'fromUser', 'toUser']);

        return view('crm.lead-transfers.show', compact('leadTransfer'));
    }

    public function edit(LeadTransfer $leadTransfer): View
    {
        $this->ensureCanAccessLeadTransfer(request()->user(), $leadTransfer);

        return view('crm.lead-transfers.form', [
            'leadTransfer' => $leadTransfer,
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'calls' => Call::query()->with('company')->latest('called_at')->limit(100)->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, LeadTransfer $leadTransfer): RedirectResponse
    {
        $this->ensureCanAccessLeadTransfer($request->user(), $leadTransfer);

        $leadTransfer->update($this->validateLeadTransfer($request));

        return redirect()
            ->route('lead-transfers.show', $leadTransfer)
            ->with('status', 'Předání leadu bylo upraveno.');
    }

    public function quickStatus(Request $request, LeadTransfer $leadTransfer): RedirectResponse
    {
        $this->ensureCanAccessLeadTransfer($request->user(), $leadTransfer);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::QUICK_STATUSES)],
        ]);

        $leadTransfer->update([
            'status' => $data['status'],
        ]);

        return redirect()
            ->to(url()->previous() ?: route('lead-transfers.index'))
            ->with('status', 'Stav predani leadu byl rychle upraven.');
    }

    private function validateLeadTransfer(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'call_id' => ['nullable', 'exists:calls,id'],
            'from_user_id' => ['nullable', 'exists:users,id'],
            'to_user_id' => ['nullable', 'exists:users,id'],
            'transferred_at' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function ensureCanAccessLeadTransfer(?User $user, LeadTransfer $leadTransfer): void
    {
        if (! $user) {
            abort(401);
        }

        if ($user->isManager()) {
            return;
        }

        $leadTransfer->loadMissing('company:id,assigned_user_id,first_caller_user_id');
        $allowed = ((int) ($leadTransfer->from_user_id ?? 0) === (int) $user->id)
            || ((int) ($leadTransfer->to_user_id ?? 0) === (int) $user->id)
            || ((int) ($leadTransfer->company?->assigned_user_id ?? 0) === (int) $user->id)
            || ((int) ($leadTransfer->company?->first_caller_user_id ?? 0) === (int) $user->id);

        abort_unless($allowed, 403);
    }
}
