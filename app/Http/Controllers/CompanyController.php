<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    private const STATUSES = ['new', 'contacted', 'follow-up', 'qualified', 'lost'];

    public function index(Request $request): View
    {
        $query = Company::query()->with('assignedUser')->latest();
        $isManager = $request->user()?->isManager() ?? false;
        $mine = $isManager
            ? (string) $request->input('mine', $request->filled('assigned_user_id') ? '0' : '1')
            : '1';

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($mine === '1' && $request->user()) {
            $query->where('assigned_user_id', $request->user()->id);
        } elseif ($isManager && $request->filled('assigned_user_id')) {
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

        $quotaUser = null;
        if ($mine === '1' && $request->user()) {
            $quotaUser = $request->user()->fresh();
        } elseif ($isManager && $request->filled('assigned_user_id')) {
            $quotaUser = User::query()->find($request->integer('assigned_user_id'));
        }

        return view('crm.companies.index', [
            'companies' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'q' => (string) $request->input('q', ''),
                'status' => (string) $request->input('status', ''),
                'assigned_user_id' => (string) $request->input('assigned_user_id', ''),
                'mine' => $mine,
            ],
            'quotaUser' => $quotaUser,
        ]);
    }

    public function create(): View
    {
        return view('crm.companies.form', [
            'company' => new Company(['status' => 'new']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
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
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($request->user()?->isManager()) {
            $data['assigned_user_id'] = $data['assigned_user_id'] ?? $request->user()?->id;
        } else {
            $data['assigned_user_id'] = $request->user()?->id;
        }

        $company = Company::create($data);

        return redirect()
            ->route('companies.show', $company)
            ->with('status', 'Firma byla vytvořena.');
    }

    public function show(Company $company): View
    {
        $company->load([
            'assignedUser',
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
                'meta' => $followUp->assignedUser?->name ? 'Přiřazeno: '.$followUp->assignedUser->name : null,
                'url' => route('follow-ups.show', $followUp),
            ]))
            ->merge($company->leadTransfers->map(fn ($transfer) => [
                'type' => 'lead-transfer',
                'at' => $transfer->transferred_at,
                'title' => 'Předání leadu: '.$transfer->status,
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
                'title' => 'Schůzka: '.$meeting->status,
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
            $currentCompany = Company::query()
                ->where('id', $currentCompanyId)
                ->where('assigned_user_id', $user->id)
                ->first();

            if ($currentCompany && $currentCompany->status === 'new') {
                return redirect()
                    ->route('companies.show', $currentCompany)
                    ->with('status', 'Nejdriv zpracujte aktualni firmu (stav uz nesmi byt "new"), pak muzete prejit na dalsi.');
            }
        }

        $companyIds = Company::query()
            ->where('assigned_user_id', $user->id)
            ->when($request->boolean('skip_lost', true), fn ($query) => $query->where('status', '!=', 'lost'))
            ->latest()
            ->pluck('id')
            ->values();

        if ($companyIds->isEmpty()) {
            return redirect()
                ->route('companies.index', ['mine' => 1])
                ->with('status', 'Ve vaší frontě teď nejsou žádné firmy.');
        }

        $currentIndex = $currentCompanyId > 0 ? $companyIds->search($currentCompanyId) : false;

        $targetId = match (true) {
            $currentIndex === false => $companyIds->first(),
            $currentIndex >= ($companyIds->count() - 1) => $companyIds->first(),
            default => $companyIds->get($currentIndex + 1),
        };

        return redirect()->route('companies.show', $targetId);
    }

    public function edit(Company $company): View
    {
        return view('crm.companies.form', [
            'company' => $company,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ico' => ['nullable', 'string', 'max:32'],
            'website' => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ]);

        if (! $request->user()?->isManager()) {
            $data['assigned_user_id'] = $company->assigned_user_id;
        }

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

        if (! $user->isManager() && $company->assigned_user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
        ]);

        $company->update([
            'status' => $data['status'],
        ]);

        return redirect()
            ->to(url()->previous() ?: route('companies.index'))
            ->with('status', 'Stav firmy byl rychle upraven.');
    }

    public function quickDefer(Request $request, Company $company): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isManager() && $company->assigned_user_id !== $user->id) {
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
}
