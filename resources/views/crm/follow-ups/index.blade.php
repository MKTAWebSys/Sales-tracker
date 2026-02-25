@extends('layouts.crm', ['title' => 'Follow-upy | Call CRM'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Follow-upy</h1>
            <p class="text-sm text-slate-600">Plánovaná navolání a další kroky po hovorech.</p>
        </div>
        <a href="{{ route('follow-ups.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Nový follow-up</a>
    </div>

    <form method="GET" action="{{ route('follow-ups.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-6">
        <div class="md:col-span-2">
            <label for="company_id" class="block text-sm font-medium text-slate-700">Firma</label>
            <select id="company_id" name="company_id" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Všechny firmy</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-slate-700">Stav</label>
            <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Vše</option>
                @foreach (['open', 'done', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        @if (auth()->user()?->isManager())
            <div>
                <label for="assigned_user_id" class="block text-sm font-medium text-slate-700">Přiřazený uživatel</label>
                <select id="assigned_user_id" name="assigned_user_id" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Všichni</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['assigned_user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label for="due_from" class="block text-sm font-medium text-slate-700">Termín od</label>
            <input id="due_from" name="due_from" type="date" value="{{ $filters['due_from'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>
        <div>
            <label for="due_to" class="block text-sm font-medium text-slate-700">Termín do</label>
            <input id="due_to" name="due_to" type="date" value="{{ $filters['due_to'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>
        <div class="md:col-span-6 flex flex-wrap items-center gap-3">
            @if (auth()->user()?->isManager())
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="mine" value="0">
                    <input type="checkbox" name="mine" value="1" class="rounded border-slate-300" @checked(($filters['mine'] ?? '1') === '1')>
                    <span>Moje follow-upy (výchozí)</span>
                </label>
            @else
                <input type="hidden" name="mine" value="1">
                <span class="text-xs text-slate-500">Zobrazeny jsou vaše přiřazené follow-upy.</span>
            @endif
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filtrovat</button>
            <a href="{{ route('follow-ups.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Reset</a>
        </div>
    </form>

    <form method="POST" action="{{ route('follow-ups.bulk-complete') }}">
        @csrf
        @foreach (($filters ?? []) as $filterKey => $filterValue)
            @if ($filterValue !== '')
                <input type="hidden" name="{{ $filterKey }}" value="{{ $filterValue }}">
            @endif
        @endforeach

        <div class="mb-3 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">
                Označit vybrané jako hotové
            </button>
            <span class="text-xs text-slate-500">Nejlépe funguje pro otevřené follow-upy v aktuálním filtru.</span>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">
                        <input type="checkbox" id="select_all_followups" class="rounded border-slate-300">
                    </th>
                    <th class="px-4 py-3">Firma</th>
                    <th class="px-4 py-3">Termín</th>
                    <th class="px-4 py-3">Stav</th>
                    <th class="px-4 py-3">Přiřazeno</th>
                    <th class="px-4 py-3">Související hovor</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($followUps as $followUp)
                    @php
                        $rowClass = match ($followUp->status) {
                            'open' => ($followUp->due_at && $followUp->due_at->isPast()) ? 'bg-rose-50/60' : 'bg-amber-50/60',
                            'done' => 'bg-emerald-50/50',
                            'cancelled' => 'bg-slate-50/70',
                            default => '',
                        };
                    @endphp
                    <tr class="{{ $rowClass }} cursor-pointer hover:brightness-[0.99]" data-row-link="{{ route('follow-ups.show', $followUp) }}">
                        <td class="px-4 py-3">
                            <input
                                type="checkbox"
                                name="follow_up_ids[]"
                                value="{{ $followUp->id }}"
                                class="follow-up-checkbox rounded border-slate-300"
                                @checked($followUp->status === 'done')
                            >
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $followUp->company?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $followUp->due_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3" data-row-link-ignore>
                            @php
                                $statusSelectClass = match ($followUp->status) {
                                    'open' => ($followUp->due_at && $followUp->due_at->isPast()) ? 'bg-rose-50 text-rose-900 border-rose-300' : 'bg-amber-50 text-amber-900 border-amber-300',
                                    'done' => 'bg-emerald-50 text-emerald-900 border-emerald-300',
                                    'cancelled' => 'bg-slate-50 text-slate-900 border-slate-300',
                                    default => 'bg-slate-50 text-slate-900 border-slate-300',
                                };
                            @endphp
                            <form method="POST" action="{{ route('follow-ups.quick-status', $followUp) }}" class="js-inline-save-form flex items-center gap-2" data-row-link-ignore>
                                @csrf
                                <select name="status" class="js-inline-save-select min-w-32 rounded-md py-1 text-xs {{ $statusSelectClass }}" data-row-link-ignore data-initial-value="{{ $followUp->status }}">
                                    @foreach (['open', 'done', 'cancelled'] as $statusOption)
                                        <option value="{{ $statusOption }}" @selected($followUp->status === $statusOption)>{{ $statusOption }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="js-inline-save-btn invisible rounded-md bg-slate-700 px-2 py-1 text-xs font-medium text-white" data-row-link-ignore>OK</button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $followUp->assignedUser?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $followUp->call_id ?: '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('follow-ups.show', $followUp) }}" class="text-slate-700 hover:text-slate-900">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Zatím žádné follow-upy.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select_all_followups');
            if (!selectAll) return;

            const checkboxes = Array.from(document.querySelectorAll('.follow-up-checkbox'));
            selectAll.addEventListener('change', function () {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
            });
        });
    </script>

    <div class="mt-4">{{ $followUps->links() }}</div>
@endsection
