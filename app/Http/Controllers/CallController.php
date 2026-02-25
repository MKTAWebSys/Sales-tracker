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
use Illuminate\Support\Arr;
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
        $call = Call::create([
            'company_id' => $company->id,
            'caller_id' => $request->user()?->id,
            'called_at' => now(),
            'outcome' => 'pending',
        ]);

        if ($company->status === 'new') {
            $company->update(['status' => 'contacted']);
        }

        return redirect()
            ->route('calls.finish', $call)
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
        $companyStatus = Arr::pull($data, 'company_status');
        $data['caller_id'] = $request->user()?->id;

        $call = Call::create($data);
        $createdItems = $this->syncNextActions($call);
        $this->syncCompanyStatus($call, $companyStatus);

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
        return view('crm.calls.form', [
            'call' => $call,
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'companyStatuses' => self::COMPANY_STATUSES,
            'flowMode' => 'finish',
        ]);
    }

    public function update(Request $request, Call $call): RedirectResponse
    {
        $data = $this->validateCall($request);
        $companyStatus = Arr::pull($data, 'company_status');

        $call->update($data);
        $createdItems = $this->syncNextActions($call);
        $this->syncCompanyStatus($call, $companyStatus);

        $baseMessage = (string) $request->input('flow_mode') === 'finish'
            ? 'Hovor byl ukoncen a ulozen.'
            : 'Hovor byl upraven.';

        $statusMessage = $this->buildSavedStatusMessage($baseMessage, $createdItems);
        $wantsNextCompany = (string) $request->input('submit_action') === 'save_next_company'
            && (string) $request->input('flow_mode') === 'finish';

        if ($wantsNextCompany) {
            return redirect()
                ->route('companies.next-mine', ['current_company_id' => $call->company_id, 'skip_lost' => 1])
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
            'company_status' => ['nullable', 'in:'.implode(',', self::COMPANY_STATUSES)],
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

    private function syncCompanyStatus(Call $call, ?string $explicitStatus): void
    {
        $targetStatus = $explicitStatus ?: ($call->next_follow_up_at ? 'follow-up' : null);

        if (! $targetStatus || ! in_array($targetStatus, self::COMPANY_STATUSES, true)) {
            return;
        }

        $call->company()->update(['status' => $targetStatus]);
    }
}
