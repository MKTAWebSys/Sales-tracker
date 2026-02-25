@extends('layouts.crm', ['title' => 'Firmy | Call CRM'])

@section('content')
    @php
        $isManager = auth()->user()?->isManager() ?? false;
    @endphp

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Firmy</h1>
            <p class="text-sm text-slate-600">CRM seznam firem + fronta pro prvni osloveni (first caller queue).</p>
        </div>
        <a href="{{ route('companies.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            Nova firma
        </a>
    </div>

    @if (!empty($quotaUser) && ($quotaUser->call_target_count || $quotaUser->call_target_until))
        <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-900">
            <div class="font-medium">
                Cil obvolani
                @if (($filters['mine'] ?? '1') === '1')
                    (moje firmy)
                @elseif (!empty($filters['assigned_user_id']))
                    (uzivatel: {{ $quotaUser->name }})
                @endif
            </div>
            <div class="mt-1">
                {{ $quotaUser->call_target_count ? $quotaUser->call_target_count.' firem' : 'Pocet neni nastaven' }}
                @if ($quotaUser->call_target_until)
                    | do {{ $quotaUser->call_target_until->format('Y-m-d') }}
                @endif
            </div>
            <div class="mt-1 text-xs text-blue-800/80">
                Akt. zobrazeno ve filtru: {{ $companies->total() }} firem
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('companies.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-6">
        <div class="md:col-span-2">
            <label for="q" class="block text-sm font-medium text-slate-700">Hledat (nazev / ICO)</label>
            <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>

        <div>
            <label for="status" class="block text-sm font-medium text-slate-700">Stav</label>
            <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Vse</option>
                @foreach (['new', 'contacted', 'follow-up', 'qualified', 'lost'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        @if ($isManager)
            <div>
                <label for="assigned_user_id" class="block text-sm font-medium text-slate-700">Owner</label>
                <select id="assigned_user_id" name="assigned_user_id" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Vse</option>
                    @foreach (($users ?? collect()) as $userOption)
                        <option value="{{ $userOption->id }}" @selected((string) ($filters['assigned_user_id'] ?? '') === (string) $userOption->id)>{{ $userOption->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="first_caller_user_id" class="block text-sm font-medium text-slate-700">First caller</label>
                <select id="first_caller_user_id" name="first_caller_user_id" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Vse</option>
                    <option value="null" @selected(($filters['first_caller_user_id'] ?? '') === 'null')>Neprirazene</option>
                    @foreach (($users ?? collect()) as $userOption)
                        <option value="{{ $userOption->id }}" @selected((string) ($filters['first_caller_user_id'] ?? '') === (string) $userOption->id)>{{ $userOption->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="{{ $isManager ? '' : 'md:col-span-2' }}">
            <label class="block text-sm font-medium text-slate-700">Queue filtr</label>
            <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="unassigned_queue_only" value="1" class="rounded border-slate-300" @checked(!empty($filters['unassigned_queue_only'] ?? false))>
                <span>Jen new + bez first caller</span>
            </label>
        </div>

        <div class="flex flex-wrap items-center gap-3 md:col-span-6">
            @if ($isManager)
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="mine" value="0">
                    <input type="checkbox" name="mine" value="1" class="rounded border-slate-300" @checked(($filters['mine'] ?? '1') === '1')>
                    <span>Moje firmy (vychozi)</span>
                </label>
            @else
                <input type="hidden" name="mine" value="1">
                <span class="text-xs text-slate-500">Zobrazeny jsou firmy, kde jste owner nebo first caller.</span>
            @endif

            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filtrovat</button>
            <a href="{{ route('companies.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Reset</a>
        </div>
    </form>

    <form id="companies-bulk-form" method="POST" action="{{ route('companies.bulk') }}" class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        @csrf

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Hromadne akce (firmy)</h2>
                <p class="text-sm text-slate-600">Vybrano: <span class="js-bulk-selected-count font-semibold">0</span> firem (na aktualni strance).</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                <label class="inline-flex items-center gap-2 rounded-md bg-slate-50 px-3 py-2 ring-1 ring-slate-200">
                    <input type="checkbox" class="js-bulk-toggle-all rounded border-slate-300">
                    <span>Vybrat vse na strance</span>
                </label>
                <button type="button" class="js-bulk-select-new rounded-md bg-amber-50 px-3 py-2 font-medium text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100">
                    Vybrat vse new
                </button>
            </div>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-12">
            <div class="lg:col-span-3">
                <label for="bulk_action" class="block text-sm font-medium text-slate-700">Akce</label>
                <select id="bulk_action" name="bulk_action" class="js-bulk-action mt-1 w-full rounded-md border-slate-300" required>
                    <option value="">Vyber akci</option>
                    @if ($isManager)
                        <option value="assign_first_caller">Priradit first caller</option>
                        <option value="claim_first_caller">Vzit si (first caller)</option>
                    @else
                        <option value="claim_first_caller">Vzit si (first caller)</option>
                    @endif
                    <option value="unassign_first_caller">Odebrat first caller</option>
                    <option value="change_status">Zmenit status</option>
                    <option value="append_note">Pridat poznamku</option>
                </select>
            </div>

            @if ($isManager)
                <div class="js-bulk-first-caller-wrap hidden lg:col-span-3">
                    <label for="first_caller_user_id_bulk" class="block text-sm font-medium text-slate-700">First caller (cil)</label>
                    <select id="first_caller_user_id_bulk" name="first_caller_user_id" class="mt-1 w-full rounded-md border-slate-300">
                        <option value="">Vyber uzivatele</option>
                        @foreach (($users ?? collect()) as $userOption)
                            <option value="{{ $userOption->id }}">{{ $userOption->name }} ({{ $userOption->role ?? 'caller' }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="js-bulk-status-wrap hidden {{ $isManager ? 'lg:col-span-3' : 'lg:col-span-4' }}">
                <label for="bulk_status" class="block text-sm font-medium text-slate-700">Novy status</label>
                <select id="bulk_status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Vyber status</option>
                    @foreach (['new', 'contacted', 'follow-up', 'qualified', 'lost'] as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>

            <div class="js-bulk-note-wrap hidden {{ $isManager ? 'lg:col-span-4' : 'lg:col-span-6' }}">
                <label for="note_append" class="block text-sm font-medium text-slate-700">Poznamka (append)</label>
                <input id="note_append" name="note_append" type="text" maxlength="5000" class="mt-1 w-full rounded-md border-slate-300" placeholder="Prida se s timestampem a jmenem uzivatele">
            </div>

            <div class="lg:col-span-2 flex items-end">
                <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Provest</button>
            </div>
        </div>

        <p class="mt-3 text-xs text-slate-500">Queue akce (priradit/odebrat first caller) plati jen pro firmy ve stavu <code>new</code> bez prvniho kontaktu.</p>

    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="w-10 px-3 py-3" data-row-link-ignore><span class="sr-only">Vyber</span></th>
                    <th class="px-4 py-3">Nazev</th>
                    <th class="px-4 py-3">ICO</th>
                    <th class="px-4 py-3">Stav</th>
                    <th class="px-4 py-3">Owner</th>
                    <th class="px-4 py-3">First caller</th>
                    <th class="px-4 py-3">Queue assigned</th>
                    <th class="px-4 py-3">First contacted</th>
                    <th class="px-4 py-3">Vytvoreno</th>
                    <th class="px-4 py-3 text-right">Akce</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                    @forelse ($companies as $company)
                        @php
                            $rowClass = match ($company->status) {
                                'qualified' => 'bg-emerald-50/60',
                                'follow-up' => 'bg-amber-50/60',
                                'contacted' => 'bg-blue-50/60',
                                'lost' => 'bg-rose-50/60',
                                default => '',
                            };
                            $statusSelectClass = match ($company->status) {
                                'qualified' => 'bg-emerald-50 text-emerald-900 border-emerald-300',
                                'follow-up' => 'bg-amber-50 text-amber-900 border-amber-300',
                                'contacted' => 'bg-blue-50 text-blue-900 border-blue-300',
                                'lost' => 'bg-rose-50 text-rose-900 border-rose-300',
                                default => 'bg-slate-50 text-slate-900 border-slate-300',
                            };
                            $isQueueReady = $company->status === 'new' && $company->first_contacted_at === null;
                        @endphp
                        <tr class="{{ $rowClass }} cursor-pointer hover:brightness-[0.99]" data-row-link="{{ route('companies.show', $company) }}">
                            <td class="px-3 py-3 align-top" data-row-link-ignore>
                                <input type="checkbox" name="company_ids[]" value="{{ $company->id }}" form="companies-bulk-form" class="js-bulk-company-checkbox mt-1 rounded border-slate-300" data-company-status="{{ $company->status }}" data-row-link-ignore>
                            </td>
                            <td class="px-4 py-3 align-top font-medium">
                                <div>{{ $company->name }}</div>
                                @if ($company->status === 'new')
                                    @if ($company->first_contacted_at)
                                        <div class="mt-1 text-xs text-blue-700">new / contacted once</div>
                                    @elseif ($company->first_caller_user_id)
                                        <div class="mt-1 text-xs text-emerald-700">new / queued</div>
                                    @else
                                        <div class="mt-1 text-xs text-amber-700">new / unassigned queue</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-slate-600">{{ $company->ico ?: '-' }}</td>
                            <td class="px-4 py-3 align-top" data-row-link-ignore>
                                <form method="POST" action="{{ route('companies.quick-status', $company) }}" class="js-inline-save-form flex items-center gap-2" data-row-link-ignore>
                                    @csrf
                                    <select name="status" class="js-inline-save-select min-w-36 rounded-md py-1 text-xs {{ $statusSelectClass }}" data-row-link-ignore data-initial-value="{{ $company->status }}">
                                        @foreach (['new', 'contacted', 'follow-up', 'qualified', 'lost'] as $statusOption)
                                            <option value="{{ $statusOption }}" @selected($company->status === $statusOption)>{{ $statusOption }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="js-inline-save-btn invisible rounded-md bg-slate-700 px-2 py-1 text-xs font-medium text-white" data-row-link-ignore>OK</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 align-top text-slate-600">{{ $company->assignedUser?->name ?? '-' }}</td>
                            <td class="px-4 py-3 align-top text-slate-600">
                                <div>{{ $company->firstCaller?->name ?? '-' }}</div>
                                @if (! $isQueueReady)
                                    <div class="mt-1 text-xs text-slate-400">queue closed</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-slate-600">{{ $company->first_caller_assigned_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 align-top text-slate-600">{{ $company->first_contacted_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 align-top text-slate-600">{{ $company->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 align-top text-right" data-row-link-ignore>
                                <div class="flex justify-end gap-2" data-row-link-ignore>
                                    <a href="{{ route('companies.calls.start', $company) }}" class="rounded-md bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white">Zahajit hovor</a>
                                    <a href="{{ route('companies.show', $company) }}" class="text-slate-700 hover:text-slate-900">Detail</a>
                                </div>
                            </td>
                        </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-10 text-center text-slate-500">Zatim zadne firmy.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $companies->links() }}
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const actionSelect = document.querySelector('.js-bulk-action');
            const firstCallerWrap = document.querySelector('.js-bulk-first-caller-wrap');
            const statusWrap = document.querySelector('.js-bulk-status-wrap');
            const noteWrap = document.querySelector('.js-bulk-note-wrap');
            const allToggle = document.querySelector('.js-bulk-toggle-all');
            const selectNewButton = document.querySelector('.js-bulk-select-new');
            const countEl = document.querySelector('.js-bulk-selected-count');
            const checkboxes = Array.from(document.querySelectorAll('.js-bulk-company-checkbox'));

            const updateBulkUi = function () {
                const action = actionSelect ? actionSelect.value : '';
                if (firstCallerWrap) firstCallerWrap.classList.toggle('hidden', action !== 'assign_first_caller');
                if (statusWrap) statusWrap.classList.toggle('hidden', action !== 'change_status');
                if (noteWrap) noteWrap.classList.toggle('hidden', action !== 'append_note');
            };

            const updateSelectedCount = function () {
                const count = checkboxes.filter((checkbox) => checkbox.checked).length;
                if (countEl) countEl.textContent = String(count);
                if (allToggle && checkboxes.length > 0) {
                    allToggle.checked = count === checkboxes.length;
                    allToggle.indeterminate = count > 0 && count < checkboxes.length;
                }
            };

            if (actionSelect) {
                actionSelect.addEventListener('change', updateBulkUi);
                updateBulkUi();
            }

            if (allToggle) {
                allToggle.addEventListener('change', function () {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = allToggle.checked;
                    });
                    updateSelectedCount();
                });
            }

            if (selectNewButton) {
                selectNewButton.addEventListener('click', function () {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = checkbox.getAttribute('data-company-status') === 'new';
                    });
                    updateSelectedCount();
                });
            }

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelectedCount));
            updateSelectedCount();
        });
    </script>
@endsection
