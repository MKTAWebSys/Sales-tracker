<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FollowUp;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompanyController extends Controller
{
    private const STATUSES = ['new', 'contacted', 'follow-up', 'qualified', 'lost'];
    private const BULK_ACTIONS = [
        'assign_first_caller',
        'claim_first_caller',
        'unassign_first_caller',
        'change_status',
        'append_note',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user?->isManager() ?? false;
        $mine = $isManager
            ? (string) $request->input('mine', $request->filled('assigned_user_id') ? '0' : '1')
            : '1';

        $query = Company::query()->with(['assignedUser', 'firstCaller']);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($mine === '1' && $user) {
            if ($isManager) {
                $query->where('assigned_user_id', $user->id);
            } else {
                $query->where(function ($subQuery) use ($user) {
                    $subQuery
                        ->where('assigned_user_id', $user->id)
                        ->orWhere('first_caller_user_id', $user->id);
                });
            }
        } elseif ($isManager && $request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->integer('assigned_user_id'));
        }

        if ($isManager && $request->filled('first_caller_user_id')) {
            if ($request->input('first_caller_user_id') === 'null') {
                $query->whereNull('first_caller_user_id');
            } else {
                $query->where('first_caller_user_id', $request->integer('first_caller_user_id'));
            }
        }

        if ($request->boolean('unassigned_queue_only')) {
            $query->newUncontacted()->whereNull('first_caller_user_id');
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('ico', 'like', "%{$search}%");
            });
        }

        if ($user) {
            $query
                ->orderByRaw(
                    "case when status = 'new' and first_contacted_at is null and first_caller_user_id = ? then 0 else 1 end",
                    [$user->id]
                )
                ->orderByRaw(
                    "case when status = 'new' and first_contacted_at is null and first_caller_user_id = ? then first_caller_assigned_at end asc",
                    [$user->id]
                );
        }

        $query->latest('created_at');

        $quotaUser = null;
        if ($mine === '1' && $user) {
            $quotaUser = $user->fresh();
        } elseif ($isManager && $request->filled('assigned_user_id')) {
            $quotaUser = User::query()->find($request->integer('assigned_user_id'));
        }

        return view('crm.companies.index', [
            'companies' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'q' => (string) $request->input('q', ''),
                'status' => (string) $request->input('status', ''),
                'assigned_user_id' => (string) $request->input('assigned_user_id', ''),
                'first_caller_user_id' => (string) $request->input('first_caller_user_id', ''),
                'mine' => $mine,
                'unassigned_queue_only' => $request->boolean('unassigned_queue_only'),
            ],
            'quotaUser' => $quotaUser,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'role']),
            'bulkActions' => self::BULK_ACTIONS,
        ]);
    }

    public function create(): View
    {
        return view('crm.companies.form', [
            'company' => new Company(['status' => 'new']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $this->validateCompany($request, $user?->isManager() ?? false);

        if (! ($user?->isManager() ?? false)) {
            $data['assigned_user_id'] = $user?->id;
            $data['first_caller_user_id'] = $user?->id;
        } else {
            $data['assigned_user_id'] = $data['assigned_user_id'] ?? $user?->id;
        }

        $this->normalizeQueueFields($data);

        $company = Company::create($data);

        return redirect()
            ->route('companies.show', $company)
            ->with('status', 'Firma byla vytvorena.');
    }

    public function show(Company $company): View
    {
        $company->load([
            'assignedUser',
            'firstCaller',
            'calls' => fn ($query) => $query->with(['caller'])->latest('called_at')->limit(10),
            'followUps' => fn ($query) => $query->with(['assignedUser'])->latest('due_at')->limit(10),
            'leadTransfers' => fn ($query) => $query->with(['fromUser', 'toUser'])->latest('transferred_at')->limit(10),
            'meetings' => fn ($query) => $query->latest('scheduled_at')->limit(10),
        ]);

        $timeline = collect()
            ->merge($company->calls->map(fn ($call) => [
                'type' => 'call',
                'at' => $call->called_at,
                'title' => 'Hovor: '.$call->outcome,
                'summary' => $call->summary,
                'meta' => $call->caller?->name ? 'Volal: '.$call->caller->name : null,
                'url' => route('calls.show', $call),
                'call' => $call,
            ]))
            ->merge($company->followUps->map(fn ($followUp) => [
                'type' => 'follow-up',
                'at' => $followUp->due_at,
                'title' => 'Follow-up: '.$followUp->status,
                'summary' => $followUp->note,
                'meta' => $followUp->assignedUser?->name ? 'Prirazeno: '.$followUp->assignedUser->name : null,
                'url' => route('follow-ups.show', $followUp),
            ]))
            ->merge($company->leadTransfers->map(fn ($transfer) => [
                'type' => 'lead-transfer',
                'at' => $transfer->transferred_at,
                'title' => 'Predani leadu: '.$transfer->status,
                'summary' => $transfer->note,
                'meta' => trim(collect([
                    $transfer->fromUser?->name ? 'Od: '.$transfer->fromUser->name : null,
                    $transfer->toUser?->name ? 'Komu: '.$transfer->toUser->name : null,
                ])->filter()->implode(' | ')),
                'url' => route('lead-transfers.show', $transfer),
            ]))
            ->merge($company->meetings->map(fn ($meeting) => [
                'type' => 'meeting',
                'at' => $meeting->scheduled_at,
                'title' => 'Schuzka: '.$meeting->status,
                'summary' => $meeting->note,
                'meta' => 'Forma: '.$meeting->mode,
                'url' => route('meetings.show', $meeting),
            ]))
            ->sortByDesc(fn ($item) => $item['at']?->getTimestamp() ?? 0)
            ->take(20)
            ->values();

        return view('crm.companies.show', compact('company', 'timeline'));
    }

    public function nextMine(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $currentCompanyId = $request->integer('current_company_id');
        if ($currentCompanyId > 0) {
            $currentCompany = Company::query()->whereKey($currentCompanyId)->first();

            if ($currentCompany && $this->isQueuedCompanyForUser($currentCompany, $user->id) && $currentCompany->status === 'new') {
                return redirect()
                    ->route('companies.show', $currentCompany)
                    ->with('status', 'Nejdriv zpracujte aktualni firmu (stav uz nesmi byt "new"), pak muzete prejit na dalsi.');
            }
        }

        $queuedIds = Company::query()
            ->queuedForCaller($user->id)
            ->orderBy('first_caller_assigned_at')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        $companyIds = $queuedIds->isNotEmpty()
            ? $queuedIds
            : Company::query()
                ->where('assigned_user_id', $user->id)
                ->when($request->boolean('skip_lost', true), fn ($query) => $query->where('status', '!=', 'lost'))
                ->latest('created_at')
                ->pluck('id')
                ->values();

        if ($companyIds->isEmpty()) {
            return redirect()
                ->route('companies.index', ['mine' => 1])
                ->with('status', 'Ve vasi fronte ted nejsou zadne firmy.');
        }

        $currentIndex = $currentCompanyId > 0 ? $companyIds->search($currentCompanyId) : false;
        $targetId = match (true) {
            $currentIndex === false => $companyIds->first(),
            $currentIndex >= ($companyIds->count() - 1) => $companyIds->first(),
            default => $companyIds->get($currentIndex + 1),
        };

        return redirect()->route('companies.show', $targetId);
    }

    public function queueMine(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 401);

        $companies = Company::query()
            ->with(['assignedUser', 'firstCaller'])
            ->queuedForCaller($user->id)
            ->orderBy('first_caller_assigned_at')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $followUps = FollowUp::query()
            ->with('company')
            ->where('assigned_user_id', $user->id)
            ->where('status', 'open')
            ->orderBy('due_at')
            ->limit(8)
            ->get();

        $meetings = Meeting::query()
            ->with('company')
            ->whereIn('status', ['planned', 'confirmed'])
            ->whereHas('call', fn ($query) => $query->where('caller_id', $user->id))
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        return view('crm.companies.queue-mine', [
            'companies' => $companies,
            'followUps' => $followUps,
            'meetings' => $meetings,
        ]);
    }

    public function edit(Company $company): View
    {
        return view('crm.companies.form', [
            'company' => $company,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $user = $request->user();
        $isManager = $user?->isManager() ?? false;
        $data = $this->validateCompany($request, $isManager);

        if (! $isManager) {
            $data['assigned_user_id'] = $company->assigned_user_id;
            $data['first_caller_user_id'] = $company->first_caller_user_id;
        }

        $this->normalizeQueueFields($data, $company);

        $company->update($data);

        return redirect()
            ->route('companies.show', $company)
            ->with('status', 'Firma byla upravena.');
    }

    public function quickStatus(Request $request, Company $company): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isManager() && ! $this->userCanWorkWithCompany($company, $user->id)) {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
        ]);

        $company->update(['status' => $data['status']]);

        return redirect()->to(url()->previous() ?: route('companies.index'))
            ->with('status', 'Stav firmy byl rychle upraven.');
    }

    public function quickDefer(Request $request, Company $company): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isManager() && ! $this->userCanWorkWithCompany($company, $user->id)) {
            abort(403);
        }

        $assignedUserId = $company->assigned_user_id ?: $user->id;
        $dueAt = Carbon::now()->addDay()->setTime(9, 0);

        $existingFollowUp = FollowUp::query()
            ->where('company_id', $company->id)
            ->where('assigned_user_id', $assignedUserId)
            ->where('status', 'open')
            ->whereBetween('due_at', [$dueAt->copy()->startOfDay(), $dueAt->copy()->endOfDay()])
            ->first();

        if (! $existingFollowUp) {
            FollowUp::query()->create([
                'company_id' => $company->id,
                'call_id' => null,
                'assigned_user_id' => $assignedUserId,
                'due_at' => $dueAt,
                'status' => 'open',
                'note' => 'Rychle odlozeno z detailu firmy (bez hovoru).',
            ]);
        }

        if ($company->status === 'new' || $company->status === 'contacted') {
            $company->update(['status' => 'follow-up']);
        }

        return redirect()
            ->route('companies.next-mine', ['current_company_id' => $company->id, 'skip_lost' => 1])
            ->with('status', 'Firma byla odlozena na follow-up a presunuta na dalsi ve fronte.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'company_ids' => ['required', 'array', 'min:1'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
            'bulk_action' => ['required', 'in:'.implode(',', self::BULK_ACTIONS)],
            'first_caller_user_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'in:'.implode(',', self::STATUSES)],
            'note_append' => ['nullable', 'string', 'max:5000'],
        ]);

        $companies = Company::query()
            ->whereIn('id', $data['company_ids'])
            ->get();

        [$updated, $skipped] = DB::transaction(function () use ($companies, $data, $user) {
            return match ($data['bulk_action']) {
                'assign_first_caller' => $this->bulkAssignFirstCaller($companies, $user, $data['first_caller_user_id'] ?? null),
                'claim_first_caller' => $this->bulkAssignFirstCaller($companies, $user, $user->id, true),
                'unassign_first_caller' => $this->bulkUnassignFirstCaller($companies, $user),
                'change_status' => $this->bulkChangeStatus($companies, $user, $data['status'] ?? null),
                'append_note' => $this->bulkAppendNote($companies, $user, (string) ($data['note_append'] ?? '')),
            };
        });

        return redirect()->to(url()->previous() ?: route('companies.index'))
            ->with('status', "Hromadna akce hotova. Upraveno: {$updated}, preskoceno: {$skipped}.");
    }

    private function validateCompany(Request $request, bool $isManager): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'ico' => ['nullable', 'string', 'max:32'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'first_caller_user_id' => ['nullable', 'exists:users,id'],
            'first_caller_assigned_at' => ['nullable', 'date'],
            'first_contacted_at' => ['nullable', 'date'],
        ];

        if (! $isManager) {
            unset($rules['assigned_user_id'], $rules['first_caller_user_id'], $rules['first_caller_assigned_at'], $rules['first_contacted_at']);
        }

        return $request->validate($rules);
    }

    private function normalizeQueueFields(array &$data, ?Company $company = null): void
    {
        if (array_key_exists('first_caller_user_id', $data)) {
            $incomingFirstCaller = $data['first_caller_user_id'] ?: null;
            $previousFirstCaller = $company?->first_caller_user_id;

            if ($incomingFirstCaller === null) {
                $data['first_caller_user_id'] = null;
                $data['first_caller_assigned_at'] = null;
            } else {
                $data['first_caller_user_id'] = (int) $incomingFirstCaller;

                $firstCallerChanged = $company === null || (int) $incomingFirstCaller !== (int) $previousFirstCaller;

                if ($firstCallerChanged && empty($data['first_caller_assigned_at'])) {
                    $data['first_caller_assigned_at'] = now();
                }
            }
        }

        if (($data['status'] ?? $company?->status) !== 'new' && array_key_exists('first_caller_user_id', $data) && empty($data['first_contacted_at'])) {
            // Do not force a timestamp here; allow manual edits for imported historical data.
        }
    }

    private function isQueuedCompanyForUser(Company $company, int $userId): bool
    {
        return $company->status === 'new'
            && $company->first_contacted_at === null
            && (int) $company->first_caller_user_id === $userId;
    }

    private function userCanWorkWithCompany(Company $company, int $userId): bool
    {
        return (int) $company->assigned_user_id === $userId
            || (int) $company->first_caller_user_id === $userId;
    }

    private function canQueueAssign(Company $company): bool
    {
        return $company->status === 'new' && $company->first_contacted_at === null;
    }

    private function bulkAssignFirstCaller(iterable $companies, User $user, ?int $targetUserId, bool $forceSelf = false): array
    {
        $updated = 0;
        $skipped = 0;

        if ($forceSelf) {
            $targetUserId = $user->id;
        }

        if (! $targetUserId) {
            return [0, is_countable($companies) ? count($companies) : 0];
        }

        if (! $user->isManager() && $targetUserId !== $user->id) {
            abort(403);
        }

        foreach ($companies as $company) {
            if (! $this->canQueueAssign($company)) {
                $skipped++;
                continue;
            }

            if ($company->first_caller_user_id !== null) {
                $skipped++;
                continue;
            }

            $company->update([
                'first_caller_user_id' => $targetUserId,
                'first_caller_assigned_at' => now(),
            ]);
            $updated++;
        }

        return [$updated, $skipped];
    }

    private function bulkUnassignFirstCaller(iterable $companies, User $user): array
    {
        $updated = 0;
        $skipped = 0;

        foreach ($companies as $company) {
            if (! $this->canQueueAssign($company)) {
                $skipped++;
                continue;
            }

            if (! $user->isManager() && (int) $company->first_caller_user_id !== $user->id) {
                $skipped++;
                continue;
            }

            if ($company->first_caller_user_id === null) {
                $skipped++;
                continue;
            }

            $company->update([
                'first_caller_user_id' => null,
                'first_caller_assigned_at' => null,
            ]);
            $updated++;
        }

        return [$updated, $skipped];
    }

    private function bulkChangeStatus(iterable $companies, User $user, ?string $status): array
    {
        if (! $status) {
            return [0, is_countable($companies) ? count($companies) : 0];
        }

        $updated = 0;
        $skipped = 0;

        foreach ($companies as $company) {
            if (! $user->isManager() && ! $this->userCanWorkWithCompany($company, $user->id)) {
                $skipped++;
                continue;
            }

            if ($company->status === $status) {
                $skipped++;
                continue;
            }

            $company->update(['status' => $status]);
            $updated++;
        }

        return [$updated, $skipped];
    }

    private function bulkAppendNote(iterable $companies, User $user, string $noteAppend): array
    {
        $noteAppend = trim($noteAppend);
        if ($noteAppend === '') {
            return [0, is_countable($companies) ? count($companies) : 0];
        }

        $updated = 0;
        $skipped = 0;
        $prefix = now()->format('Y-m-d H:i').' | '.($user->name ?: 'user').': ';

        foreach ($companies as $company) {
            if (! $user->isManager() && ! $this->userCanWorkWithCompany($company, $user->id)) {
                $skipped++;
                continue;
            }

            $newChunk = $prefix.$noteAppend;
            $company->update([
                'notes' => trim((string) $company->notes) === ''
                    ? $newChunk
                    : rtrim((string) $company->notes).PHP_EOL.PHP_EOL.$newChunk,
            ]);
            $updated++;
        }

        return [$updated, $skipped];
    }
}
