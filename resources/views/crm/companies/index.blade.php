@extends('layouts.crm', ['title' => 'Firmy | Call CRM'])

@section('content')
    @php
        $isManager = auth()->user()?->isManager() ?? false;
    @endphp

    <form method="GET" action="{{ route('companies.index') }}" class="mb-4 grid gap-2 rounded-xl bg-white p-2.5 shadow-sm ring-1 ring-slate-200 md:grid-cols-7">
        <div class="md:col-span-2">
            <label for="q" class="block text-xs font-medium text-slate-700">Hledat (nazev / ICO)</label>
            <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" class="mt-1 h-8 w-full rounded-md border-slate-300 text-xs">
        </div>

        <div>
            <label for="status" class="block text-xs font-medium text-slate-700">Stav</label>
            <select id="status" name="status" class="mt-1 h-8 w-full rounded-md border-slate-300 text-xs">
                <option value="">Vse</option>
                @foreach (['new', 'follow-up', 'meeting', 'deal', 'lost'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        @if ($isManager)
            <div>
                <label for="assigned_user_id" class="block text-xs font-medium text-slate-700">Aktivni resici</label>
                <select id="assigned_user_id" name="assigned_user_id" class="mt-1 h-8 w-full rounded-md border-slate-300 text-xs">
                    <option value="">Vse</option>
                    @foreach (($users ?? collect()) as $userOption)
                        <option value="{{ $userOption->id }}" @selected((string) ($filters['assigned_user_id'] ?? '') === (string) $userOption->id)>{{ $userOption->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="first_caller_user_id" class="block text-xs font-medium text-slate-700">First caller</label>
                <select id="first_caller_user_id" name="first_caller_user_id" class="mt-1 h-8 w-full rounded-md border-slate-300 text-xs">
                    <option value="">Vse</option>
                    <option value="null" @selected(($filters['first_caller_user_id'] ?? '') === 'null')>Neprirazene</option>
                    @foreach (($users ?? collect()) as $userOption)
                        <option value="{{ $userOption->id }}" @selected((string) ($filters['first_caller_user_id'] ?? '') === (string) $userOption->id)>{{ $userOption->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="{{ $isManager ? '' : 'md:col-span-2' }}">
            <label class="block text-xs font-medium text-slate-700">Rychly filtr</label>
            <div class="mt-1 flex items-center gap-2">
                <button
                    type="submit"
                    name="quick_filter"
                    value="overdue"
                    class="h-8 rounded-md px-2.5 text-xs font-medium ring-1 {{ ($filters['quick_filter'] ?? '') === 'overdue' ? 'bg-rose-100 text-rose-800 ring-rose-300' : 'bg-slate-50 text-slate-700 ring-slate-200 hover:bg-slate-100' }}"
                >
                    Overdue
                </button>
                <button
                    type="submit"
                    name="quick_filter"
                    value="new_unassigned"
                    class="h-8 rounded-md px-2.5 text-[11px] font-medium ring-1 {{ ($filters['quick_filter'] ?? '') === 'new_unassigned' || !empty($filters['unassigned_queue_only'] ?? false) ? 'bg-amber-100 text-amber-800 ring-amber-300' : 'bg-slate-50 text-slate-700 ring-slate-200 hover:bg-slate-100' }}"
                >
                    New bez first caller
                </button>
            </div>
        </div>

        <div class="md:col-span-1 flex items-end justify-end gap-2">
            <button type="submit" class="h-8 rounded-md bg-slate-900 px-2.5 text-xs font-medium text-white">Filtrovat</button>
            <a href="{{ route('companies.index') }}" class="inline-flex h-8 items-center rounded-md bg-slate-100 px-2.5 text-xs text-slate-700 ring-1 ring-slate-200 hover:bg-slate-200">Reset</a>
        </div>

    </form>

    <div class="mb-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_320px]">
        <form id="companies-bulk-form" method="POST" action="{{ route('companies.bulk') }}" class="rounded-xl bg-white p-2 shadow-sm ring-1 ring-slate-200">
            @csrf

            <div class="flex flex-wrap items-center gap-1.5">
                <div class="min-w-[112px] pr-1">
                    <h2 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Hromadne akce</h2>
                    <p class="text-xs text-slate-600">Vybrano: <span class="js-bulk-selected-count font-semibold">0</span></p>
                </div>

                <div class="min-w-[170px] flex-1">
                    <label for="bulk_action" class="sr-only">Akce</label>
                    <select id="bulk_action" name="bulk_action" class="js-bulk-action h-8 w-full rounded-md border-slate-300 text-xs" required>
                        <option value="">Vyber akci</option>
                        @if ($isManager)
                            <option value="assign_owner">Priradit aktivniho resiciho</option>
                            <option value="assign_first_caller">Priradit first caller</option>
                        @endif
                        <option value="unassign_first_caller">Odebrat first caller</option>
                        <option value="change_status">Zmenit status</option>
                        <option value="append_note">Pridat poznamku</option>
                    </select>
                </div>

                @if ($isManager)
                    <div class="js-bulk-owner-wrap hidden min-w-[170px] flex-1">
                        <label for="assigned_user_id_bulk" class="sr-only">Aktivni resici (cil)</label>
                        <select id="assigned_user_id_bulk" name="assigned_user_id" class="h-8 w-full rounded-md border-slate-300 text-xs">
                            <option value="">Neprirazeno</option>
                            @foreach (($users ?? collect()) as $userOption)
                                <option value="{{ $userOption->id }}">{{ $userOption->name }} ({{ $userOption->role ?? 'caller' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="js-bulk-first-caller-wrap hidden min-w-[170px] flex-1">
                        <label for="first_caller_user_id_bulk" class="sr-only">First caller (cil)</label>
                        <select id="first_caller_user_id_bulk" name="first_caller_user_id" class="h-8 w-full rounded-md border-slate-300 text-xs">
                            <option value="">Vyber uzivatele</option>
                            @foreach (($users ?? collect()) as $userOption)
                                <option value="{{ $userOption->id }}">{{ $userOption->name }} ({{ $userOption->role ?? 'caller' }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="js-bulk-status-wrap hidden min-w-[140px] flex-1">
                    <label for="bulk_status" class="sr-only">Novy status</label>
                    <select id="bulk_status" name="status" class="h-8 w-full rounded-md border-slate-300 text-xs">
                        <option value="">Vyber status</option>
                        @foreach (['new', 'follow-up', 'meeting', 'deal', 'lost'] as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="js-bulk-note-wrap hidden min-w-[220px] flex-[2]">
                    <label for="note_append" class="sr-only">Poznamka (append)</label>
                    <input id="note_append" name="note_append" type="text" maxlength="5000" class="h-8 w-full rounded-md border-slate-300 text-xs" placeholder="Prida se s timestampem a jmenem uzivatele">
                </div>

                <div class="min-w-[95px]">
                    <button type="submit" class="h-8 w-full rounded-md bg-slate-900 px-3 text-xs font-medium text-white">Provest</button>
                </div>

                <div class="ml-auto flex flex-wrap items-center gap-1.5 text-xs text-slate-600">
                    <label class="inline-flex items-center gap-1.5 rounded-md bg-slate-50 px-2 py-1 ring-1 ring-slate-200">
                        <input type="checkbox" class="js-bulk-toggle-all rounded border-slate-300">
                        <span>Vybrat vse</span>
                    </label>
                    <button type="button" class="js-bulk-select-new rounded-md bg-amber-50 px-2 py-1 font-medium text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100">
                        Jen new
                    </button>
                </div>
            </div>
        </form>

        <div class="flex items-stretch justify-end gap-2">
            @if ($isManager)
                <a
                    href="{{ route('imports.xlsx') }}"
                    class="inline-flex min-h-[32px] w-28 items-center justify-center rounded-lg bg-emerald-600 px-2 text-xs font-semibold text-white shadow-sm ring-1 ring-emerald-700/20 hover:bg-emerald-500"
                    title="Import XLSX"
                    aria-label="Import XLSX"
                >
                    Import XLSX
                </a>
            @endif
            <a
                href="{{ route('companies.create') }}"
                class="inline-flex min-h-[32px] w-28 items-center justify-center rounded-lg bg-blue-600 px-2 text-xs font-semibold text-white shadow-sm ring-1 ring-blue-700/20 hover:bg-blue-500"
                title="Nova firma"
                aria-label="Nova firma"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 4a1 1 0 011 1v4h4a1 1 0 110 2h-4v4a1 1 0 11-2 0v-4H5a1 1 0 110-2h4V5a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                <span>Nova firma</span>
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="w-10 border-r border-slate-100 px-3 py-3 text-center" data-row-link-ignore>
                        <label class="inline-flex items-center justify-center" data-row-link-ignore>
                            <input type="checkbox" class="js-bulk-toggle-all rounded border-slate-300" data-row-link-ignore>
                            <span class="sr-only">Vybrat vse</span>
                        </label>
                    </th>
                    <th class="border-r border-slate-100 px-4 py-3">Nazev</th>
                    <th class="border-r border-slate-100 px-4 py-3">ICO</th>
                    <th class="w-32 border-r border-slate-100 px-3 py-3">Stav</th>
                    <th class="w-40 border-r border-slate-100 px-3 py-3">Owner</th>
                    <th class="w-40 border-r border-slate-100 px-3 py-3">Aktivni resici</th>
                    <th class="w-40 border-r border-slate-100 px-3 py-3">First caller</th>
                    <th class="w-14 px-2 py-3 text-center">Akce</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                    @forelse ($companies as $company)
                        @php
                            $rowClass = match ($company->status) {
                                'meeting' => 'bg-blue-50/60',
                                'deal' => 'bg-emerald-50/60',
                                'follow-up' => 'bg-amber-50/60',
                                'lost' => 'bg-rose-50/60',
                                default => '',
                            };
                            $statusSelectClass = match ($company->status) {
                                'meeting' => 'bg-blue-50 text-blue-900 border-blue-300',
                                'deal' => 'bg-emerald-50 text-emerald-900 border-emerald-300',
                                'follow-up' => 'bg-amber-50 text-amber-900 border-amber-300',
                                'lost' => 'bg-rose-50 text-rose-900 border-rose-300',
                                default => 'bg-slate-50 text-slate-900 border-slate-300',
                            };
                            $isQueueReady = $company->status === 'new' && $company->first_contacted_at === null;
                        @endphp
                        <tr class="{{ $rowClass }} cursor-pointer hover:brightness-[0.99]" data-row-link="{{ route('companies.show', $company) }}">
                            <td class="border-r border-slate-100 px-3 py-3 align-middle" data-row-link-ignore>
                                <input type="checkbox" name="company_ids[]" value="{{ $company->id }}" form="companies-bulk-form" class="js-bulk-company-checkbox rounded border-slate-300" data-company-status="{{ $company->status }}" data-row-link-ignore>
                            </td>
                            <td class="border-r border-slate-100 px-4 py-3 align-middle font-medium">
                                <div>{{ $company->name }}</div>
                                @if ($company->status === 'new')
                                    @if ($company->first_contacted_at)
                                        <div class="mt-1 text-xs text-blue-700">new / probehl pokus o kontakt</div>
                                    @elseif ($company->first_caller_user_id)
                                        <div class="mt-1 text-xs text-emerald-700">new / queued</div>
                                    @else
                                        <div class="mt-1 text-xs text-amber-700">new / unassigned queue</div>
                                    @endif
                                @endif
                            </td>
                            <td class="border-r border-slate-100 px-4 py-3 align-middle text-slate-600">{{ $company->ico ?: '-' }}</td>
                            <td class="border-r border-slate-100 px-3 py-3 align-middle" data-row-link-ignore>
                                <form method="POST" action="{{ route('companies.quick-status', $company) }}" class="js-inline-save-form flex items-center gap-1" data-row-link-ignore>
                                    @csrf
                                    <div class="relative w-24" data-row-link-ignore>
                                        <select name="status" class="js-inline-save-select w-full appearance-none bg-none rounded-md py-1 pl-2 pr-5 text-[11px] leading-tight {{ $statusSelectClass }}" data-row-link-ignore data-initial-value="{{ $company->status }}">
                                            @foreach (['new', 'follow-up', 'meeting', 'deal', 'lost'] as $statusOption)
                                                <option value="{{ $statusOption }}" @selected($company->status === $statusOption)>{{ $statusOption }}</option>
                                            @endforeach
                                        </select>
                                        <span class="pointer-events-none absolute inset-y-0 right-1 flex items-center text-slate-500">
                                            <svg viewBox="0 0 20 20" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="m6 8 4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                    </div>
                                    <button type="submit" class="js-inline-save-btn invisible w-9 rounded-md bg-slate-700 px-2 py-1 text-xs font-medium text-white" data-row-link-ignore>OK</button>
                                </form>
                                @if ($company->status === 'follow-up' && ($company->overdue_follow_ups_count ?? 0) > 0)
                                    <div class="mt-1 inline-flex items-center gap-1 text-[11px] font-medium text-rose-700">
                                        <span class="inline-flex h-2 w-2 rounded-full bg-rose-500"></span>
                                        <span>overdue</span>
                                    </div>
                                @endif
                            </td>
                            <td class="border-r border-slate-100 px-3 py-3 align-middle text-slate-600">
                                {{ $company->assignedUser?->name ?? '-' }}
                            </td>
                            <td class="border-r border-slate-100 px-3 py-3 align-middle text-slate-600" data-row-link-ignore>
                                @if ($isManager)
                                    <form method="POST" action="{{ route('companies.quick-assigned-user', $company) }}" class="js-inline-save-form flex items-center gap-1" data-row-link-ignore>
                                        @csrf
                                        <select name="assigned_user_id" class="js-inline-save-select w-32 rounded-md py-1 text-xs bg-white text-slate-900 border-slate-300" data-row-link-ignore data-initial-value="{{ (string) ($company->assigned_user_id ?? '') }}">
                                            <option value="">Neprirazeno</option>
                                            @foreach (($users ?? collect()) as $userOption)
                                                <option value="{{ $userOption->id }}" @selected((int) ($company->assigned_user_id ?? 0) === (int) $userOption->id)>{{ $userOption->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="js-inline-save-btn invisible w-9 rounded-md bg-slate-700 px-2 py-1 text-xs font-medium text-white" data-row-link-ignore>OK</button>
                                    </form>
                                @else
                                    {{ $company->assignedUser?->name ?? '-' }}
                                @endif
                            </td>
                            <td class="border-r border-slate-100 px-3 py-3 align-middle text-slate-600">
                                @if ($isManager && $isQueueReady)
                                    <form method="POST" action="{{ route('companies.quick-first-caller', $company) }}" class="js-inline-save-form flex items-center gap-1" data-row-link-ignore>
                                        @csrf
                                        <select name="first_caller_user_id" class="js-inline-save-select w-32 rounded-md py-1 text-xs bg-white text-slate-900 border-slate-300" data-row-link-ignore data-initial-value="{{ (string) ($company->first_caller_user_id ?? '') }}">
                                            <option value="">Neprirazeno</option>
                                            @foreach (($users ?? collect()) as $userOption)
                                                <option value="{{ $userOption->id }}" @selected((int) ($company->first_caller_user_id ?? 0) === (int) $userOption->id)>{{ $userOption->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="js-inline-save-btn invisible w-9 rounded-md bg-slate-700 px-2 py-1 text-xs font-medium text-white" data-row-link-ignore>OK</button>
                                    </form>
                                @else
                                    <div>{{ $company->firstCaller?->name ?? '-' }}</div>
                                @endif
                                @if (! $isQueueReady)
                                    <div class="mt-1 text-xs text-slate-400">queue closed</div>
                                @endif
                            </td>
                            <td class="w-14 px-2 py-3 align-middle text-center" data-row-link-ignore>
                                <div class="flex justify-center" data-row-link-ignore>
                                    <form method="POST" action="{{ route('companies.calls.start', $company) }}" class="inline-flex" data-row-link-ignore>
                                        @csrf
                                        <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-600 text-white" title="Zahajit hovor" aria-label="Zahajit hovor">
                                            <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-slate-500">Zatim zadne firmy.</td>
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
            const ownerWrap = document.querySelector('.js-bulk-owner-wrap');
            const firstCallerWrap = document.querySelector('.js-bulk-first-caller-wrap');
            const statusWrap = document.querySelector('.js-bulk-status-wrap');
            const noteWrap = document.querySelector('.js-bulk-note-wrap');
            const allToggles = Array.from(document.querySelectorAll('.js-bulk-toggle-all'));
            const selectNewButton = document.querySelector('.js-bulk-select-new');
            const countEl = document.querySelector('.js-bulk-selected-count');
            const checkboxes = Array.from(document.querySelectorAll('.js-bulk-company-checkbox'));

            const updateBulkUi = function () {
                const action = actionSelect ? actionSelect.value : '';
                if (ownerWrap) ownerWrap.classList.toggle('hidden', action !== 'assign_owner');
                if (firstCallerWrap) firstCallerWrap.classList.toggle('hidden', action !== 'assign_first_caller');
                if (statusWrap) statusWrap.classList.toggle('hidden', action !== 'change_status');
                if (noteWrap) noteWrap.classList.toggle('hidden', action !== 'append_note');
            };

            const updateSelectedCount = function () {
                const count = checkboxes.filter((checkbox) => checkbox.checked).length;
                if (countEl) countEl.textContent = String(count);
                allToggles.forEach((toggle) => {
                    if (checkboxes.length > 0) {
                        toggle.checked = count === checkboxes.length;
                        toggle.indeterminate = count > 0 && count < checkboxes.length;
                    } else {
                        toggle.checked = false;
                        toggle.indeterminate = false;
                    }
                });
            };

            if (actionSelect) {
                actionSelect.addEventListener('change', updateBulkUi);
                updateBulkUi();
            }

            allToggles.forEach((toggle) => {
                toggle.addEventListener('change', function () {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = toggle.checked;
                    });
                    updateSelectedCount();
                });
            });

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
