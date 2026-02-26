<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\LeadTransfer;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CallController extends Controller
{
    private const COMPANY_STATUSES = ['new', 'contacted', 'follow-up', 'qualified', 'lost'];
    private const OUTCOMES = ['pending', 'no-answer', 'callback', 'interested', 'not-interested', 'meeting-booked'];

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

    public function quickStart(Request $request, Company $company): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $this->closeStalePendingCallsForCallerId($user->id);

        $activeCall = Call::query()
            ->where('caller_id', $user->id)
            ->where('outcome', 'pending')
            ->whereNull('ended_at')
            ->latest('called_at')
            ->first();

        if ($activeCall) {
            $params = ['call' => $activeCall];
            if ($request->boolean('caller_mode')) {
                $params['caller_mode'] = 1;
            }

            return redirect()
                ->route('calls.finish', $params)
                ->with('status', 'Uz mate aktivni hovor. Nejdriv ho dokoncete nebo zapisujte poznamku v prubehu hovoru.');
        }

        $call = Call::create([
            'company_id' => $company->id,
            'caller_id' => $user->id,
            'called_at' => now(),
            'outcome' => 'pending',
        ]);

        if ($company->status === 'new') {
            $company->update(['status' => 'contacted']);
        }

        $finishRouteParams = ['call' => $call];
        if ($request->boolean('caller_mode')) {
            $finishRouteParams['caller_mode'] = 1;
        }

        return redirect()
            ->route('calls.finish', $finishRouteParams)
            ->with('status', 'Hovor byl zahajen. Po ukonceni doplnte vysledek, poznamku a dalsi kroky.');
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
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'companyStatuses' => self::COMPANY_STATUSES,
            'flowMode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCall($request);
        $data['caller_id'] = $request->user()?->id;

        $call = Call::create($data);
        $createdItems = $this->syncNextActions($call);
        $this->syncFirstContactedAt($call);
        $this->syncCompanyStatus($call);

        return redirect()
            ->route('calls.show', $call)
            ->with('status', $this->buildSavedStatusMessage('Hovor byl ulozen.', $createdItems));
    }

    public function show(Call $call): View
    {
        $call->load(['company', 'caller', 'handedOverTo'])
            ->loadCount(['followUps', 'leadTransfers', 'meetings']);

        return view('crm.calls.show', compact('call'));
    }

    public function edit(Call $call): View
    {
        return view('crm.calls.form', [
            'call' => $call,
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'companyStatuses' => self::COMPANY_STATUSES,
            'flowMode' => 'edit',
        ]);
    }

    public function finish(Call $call): View
    {
        $finalizeCall = request()->boolean('finalize_call') || $call->ended_at !== null || $call->outcome !== 'pending';

        return view('crm.calls.form', [
            'call' => $call,
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'companyStatuses' => self::COMPANY_STATUSES,
            'flowMode' => 'finish',
            'finalizeCall' => $finalizeCall,
        ]);
    }

    public function end(Request $request, Call $call): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isManager() && $call->caller_id && $call->caller_id !== $user->id) {
            abort(403);
        }

        if ($call->outcome === 'pending' && $call->ended_at === null) {
            $call->update(['ended_at' => now()]);
        }

        return redirect()->route('calls.finish', [
            'call' => $call,
            'finalize_call' => 1,
            'caller_mode' => $request->boolean('caller_mode') ? 1 : null,
        ]);
    }

    public function update(Request $request, Call $call): RedirectResponse
    {
        $isFinishFlow = (string) $request->input('flow_mode') === 'finish';
        $isCallerMode = $request->boolean('caller_mode');
        $finalizeCall = $request->boolean('finalize_call') || $call->ended_at !== null || $call->outcome !== 'pending';

        if ($isFinishFlow && $call->outcome === 'pending' && $call->ended_at === null && ! $finalizeCall) {
            $data = $request->validate([
                'company_id' => ['required', 'exists:companies,id'],
                'called_at' => ['required', 'date'],
                'summary' => ['nullable', 'string'],
            ]);

            $call->update([
                'summary' => $data['summary'] ?? null,
            ]);

            $params = ['call' => $call];
            if ($isCallerMode) {
                $params['caller_mode'] = 1;
            }

            return redirect()
                ->route('calls.finish', $params)
                ->with('status', 'Poznamka k aktivnimu hovoru byla ulozena.');
        }

        $data = $this->validateCall($request);
        if ($isFinishFlow && $finalizeCall && empty($data['ended_at'])) {
            $data['ended_at'] = $call->ended_at ?: now();
        }

        if ($isFinishFlow && $finalizeCall && ($data['outcome'] ?? null) === 'pending') {
            return redirect()
                ->back()
                ->withErrors(['outcome' => 'Pri ukonceni hovoru musite vybrat finalni vysledek (nelze ponechat rozpracovano).'])
                ->withInput();
        }

        $call->update($data);
        $createdItems = $this->syncNextActions($call);
        $this->syncFirstContactedAt($call);
        $this->syncCompanyStatus($call);

        $closedStalePending = 0;
        if ($isFinishFlow && $finalizeCall && $call->caller_id) {
            $closedStalePending = $this->closeStalePendingCallsForCallerId($call->caller_id, $call->id);
        }

        $baseMessage = $isFinishFlow
            ? 'Hovor byl ukoncen a ulozen.'
            : 'Hovor byl upraven.';

        $statusMessage = $this->buildSavedStatusMessage($baseMessage, $createdItems);
        if ($closedStalePending > 0) {
            $statusMessage .= ' Automaticky uzavreno starych rozpracovanych hovoru: '.$closedStalePending.'.';
        }
        $wantsNextCompany = (string) $request->input('submit_action') === 'save_next_company'
            && $isFinishFlow;

        if ($isCallerMode && $isFinishFlow) {
            return redirect()
                ->route('caller-mode.index')
                ->with('status', $statusMessage);
        }

        if ($wantsNextCompany) {
            return redirect()
                ->route('companies.next-mine', ['current_company_id' => $call->company_id, 'skip_lost' => 1])
                ->with('status', $statusMessage);
        }

        if ($isFinishFlow) {
            return redirect()
                ->route('companies.queue.mine')
                ->with('status', $statusMessage);
        }

        return redirect()
            ->route('calls.show', $call)
            ->with('status', $statusMessage);
    }

    public function quickOutcome(Request $request, Call $call): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isManager() && $call->caller_id && $call->caller_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'outcome' => ['required', 'in:'.implode(',', self::OUTCOMES)],
        ]);

        $call->update(['outcome' => $data['outcome']]);

        return redirect()->to(url()->previous() ?: route('calls.index'))
            ->with('status', 'Vysledek hovoru byl rychle upraven.');
    }

    public function quickNote(Request $request, Call $call)
    {
        $user = $request->user();
        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            return redirect()->route('login');
        }

        if (! $user->isManager() && $call->caller_id && $call->caller_id !== $user->id) {
            abort(403);
        }

        if ($call->outcome !== 'pending' || $call->ended_at !== null) {
            $message = 'Quick note lze pridat jen k aktivnimu hovoru.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->back()->with('status', $message);
        }

        $data = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $note = trim((string) $data['note']);
        $timestampedBlock = now()->format('Y-m-d H:i:s').' | '.($user->name ?: 'user').PHP_EOL.$note;

        $call->update([
            'summary' => trim((string) $call->summary) === ''
                ? $timestampedBlock
                : rtrim((string) $call->summary).PHP_EOL.PHP_EOL.$timestampedBlock,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Poznamka ulozena',
                'summary' => $call->summary,
                'saved_at' => now()->toIso8601String(),
            ]);
        }

        return redirect()->back()->with('status', 'Poznamka byla ulozena.');
    }

    private function validateCall(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'called_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:called_at'],
            'outcome' => ['required', 'string', 'max:50'],
            'summary' => ['nullable', 'string'],
            'next_follow_up_at' => ['nullable', 'date'],
            'meeting_planned_at' => ['nullable', 'date'],
            'handed_over_to_id' => ['nullable', 'exists:users,id'],
        ]);
    }

    private function syncNextActions(Call $call): array
    {
        $created = [];

        if ($call->next_follow_up_at) {
            $followUp = FollowUp::query()->firstOrNew(['call_id' => $call->id]);
            $wasNew = ! $followUp->exists;

            $followUp->fill([
                'company_id' => $call->company_id,
                'assigned_user_id' => $call->handed_over_to_id ?: $call->caller_id,
                'due_at' => $call->next_follow_up_at,
                'status' => $followUp->status ?: 'open',
                'note' => $followUp->note ?: 'Automaticky vytvoreno z planovaneho follow-upu v hovoru.',
            ]);
            $followUp->save();

            if ($wasNew) {
                $created[] = 'follow-up';
            }
        }

        if ($call->meeting_planned_at) {
            $meeting = Meeting::query()->firstOrNew(['call_id' => $call->id]);
            $wasNew = ! $meeting->exists;

            $meeting->fill([
                'company_id' => $call->company_id,
                'scheduled_at' => $call->meeting_planned_at,
                'mode' => $meeting->mode ?: 'onsite',
                'status' => $meeting->status ?: 'planned',
                'note' => $meeting->note ?: 'Automaticky vytvoreno z planovane schuzky v hovoru.',
            ]);
            $meeting->save();

            if ($wasNew) {
                $created[] = 'schuzka';
            }
        }

        if ($call->handed_over_to_id && $call->handed_over_to_id !== $call->caller_id) {
            $transfer = LeadTransfer::query()->firstOrNew(['call_id' => $call->id]);
            $wasNew = ! $transfer->exists;

            $transfer->fill([
                'company_id' => $call->company_id,
                'from_user_id' => $call->caller_id,
                'to_user_id' => $call->handed_over_to_id,
                'transferred_at' => $call->called_at,
                'status' => $transfer->status ?: 'pending',
                'note' => $transfer->note ?: 'Automaticky vytvoreno z predani v hovoru.',
            ]);
            $transfer->save();

            if ($wasNew) {
                $created[] = 'predani leadu';
            }
        }

        return $created;
    }

    private function buildSavedStatusMessage(string $baseMessage, array $createdItems): string
    {
        if ($createdItems === []) {
            return $baseMessage;
        }

        return $baseMessage.' Automaticky vytvoreno: '.implode(', ', $createdItems).'.';
    }

    private function syncCompanyStatus(Call $call): void
    {
        $targetStatus = match ($call->outcome) {
            'not-interested' => 'lost',
            'meeting-booked' => 'qualified',
            'callback', 'no-answer' => 'follow-up',
            'interested' => $call->next_follow_up_at ? 'follow-up' : 'contacted',
            default => ($call->next_follow_up_at ? 'follow-up' : null),
        };

        if (! $targetStatus || ! in_array($targetStatus, self::COMPANY_STATUSES, true)) {
            return;
        }

        $call->company()->update(['status' => $targetStatus]);
    }

    private function syncFirstContactedAt(Call $call): void
    {
        if ($call->outcome === 'pending') {
            return;
        }

        $company = $call->company()->first(['id', 'first_contacted_at']);
        if (! $company || $company->first_contacted_at) {
            return;
        }

        $call->company()->update([
            'first_contacted_at' => $call->called_at,
        ]);
    }

    private function closeStalePendingCallsForCallerId(int $callerId, ?int $keepCallId = null): int
    {
        $staleCalls = Call::query()
            ->where('caller_id', $callerId)
            ->where('outcome', 'pending')
            ->whereNull('ended_at')
            ->when($keepCallId, fn ($query) => $query->where('id', '!=', $keepCallId))
            ->orderByDesc('called_at')
            ->get(['id', 'summary']);

        if ($staleCalls->count() <= ($keepCallId ? 0 : 1)) {
            return 0;
        }

        $callsToClose = $keepCallId ? $staleCalls : $staleCalls->slice(1);
        $closed = 0;
        $notePrefix = now()->format('Y-m-d H:i:s').' | System'.PHP_EOL
            .'Automaticky uzavreno jako stary rozpracovany hovor (single active call pravidlo).';

        foreach ($callsToClose as $staleCall) {
            $summary = trim((string) $staleCall->summary);

            Call::query()
                ->whereKey($staleCall->id)
                ->update([
                    'ended_at' => now(),
                    'outcome' => 'callback',
                    'summary' => $summary === '' ? $notePrefix : rtrim($summary).PHP_EOL.PHP_EOL.$notePrefix,
                    'updated_at' => now(),
                ]);

            $closed++;
        }

        return $closed;
    }
}
