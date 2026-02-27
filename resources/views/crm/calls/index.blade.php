@extends('layouts.crm', ['title' => 'Hovory | Call CRM'])

@section('content')
    @php
        $outcomeLabels = [
            'pending' => 'Rozpracovano',
            'no-answer' => 'Nezastizen',
            'callback' => 'Zavolat znovu',
            'interested' => 'Zajem',
            'not-interested' => 'Bez zajmu',
            'meeting-booked' => 'Schuzka domluvena',
        ];
    @endphp

    <form id="calls-filter-form" method="GET" action="{{ route('calls.index') }}" class="mb-4">
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex min-h-10 flex-1 flex-wrap items-center gap-2 rounded-lg bg-white px-2 py-2 ring-1 ring-slate-200">
                <select id="company_id" name="company_id" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" class="h-8 min-w-[14rem] rounded-md border-slate-300 py-0 text-sm">
                    <option value="">Firma: vsechny</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') === (string) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>

                <select id="outcome" name="outcome" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" class="h-8 min-w-[13rem] rounded-md border-slate-300 py-0 text-sm">
                    <option value="">Vysledek: vse</option>
                    @foreach ($outcomeLabels as $outcome => $label)
                        <option value="{{ $outcome }}" @selected(($filters['outcome'] ?? '') === $outcome)>{{ $label }}</option>
                    @endforeach
                </select>

                <select id="caller_id" name="caller_id" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" class="h-8 min-w-[11rem] rounded-md border-slate-300 py-0 text-sm">
                    <option value="">Volal: vsichni</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['caller_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>

                <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" class="h-8 rounded-md border-slate-300 py-0 text-sm" title="Datum od" aria-label="Datum od">
                <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" class="h-8 rounded-md border-slate-300 py-0 text-sm" title="Datum do" aria-label="Datum do">
            </div>

            <a href="{{ route('calls.index') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-slate-200 hover:text-slate-900" title="Reset" aria-label="Reset">
                <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16 10a6 6 0 1 1-2-4.47" />
                    <path d="M16 4v4h-4" />
                </svg>
            </a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Firma</th>
                    <th class="px-4 py-3">Volano</th>
                    <th class="px-4 py-3">Vysledek</th>
                    <th class="px-4 py-3">Volal</th>
                    <th class="px-4 py-3">Dalsi follow-up</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($calls as $call)
                    @php
                        $rowClass = match ($call->outcome) {
                            'pending' => 'bg-violet-50/60',
                            'meeting-booked' => 'bg-emerald-50/60',
                            'interested' => 'bg-blue-50/60',
                            'callback' => 'bg-amber-50/60',
                            'not-interested' => 'bg-rose-50/60',
                            default => '',
                        };
                    @endphp
                    <tr class="{{ $rowClass }} cursor-pointer hover:brightness-[0.99]" data-row-link="{{ route('calls.show', $call) }}">
                        <td class="px-4 py-3 font-medium">{{ $call->company?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $call->called_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3" data-row-link-ignore>
                            <form method="POST" action="{{ route('calls.quick-outcome', $call) }}" class="js-inline-save-form flex items-center gap-2" data-row-link-ignore>
                                @csrf
                                @php
                                    $outcomeSelectClass = match ($call->outcome) {
                                        'pending' => 'bg-violet-50 text-violet-900 border-violet-300',
                                        'meeting-booked' => 'bg-emerald-50 text-emerald-900 border-emerald-300',
                                        'interested' => 'bg-blue-50 text-blue-900 border-blue-300',
                                        'callback' => 'bg-amber-50 text-amber-900 border-amber-300',
                                        'not-interested' => 'bg-rose-50 text-rose-900 border-rose-300',
                                        default => 'bg-slate-50 text-slate-900 border-slate-300',
                                    };
                                @endphp
                                <select name="outcome" class="js-inline-save-select min-w-40 rounded-md py-1 text-xs {{ $outcomeSelectClass }}" data-row-link-ignore data-initial-value="{{ $call->outcome }}">
                                    @foreach ($outcomeLabels as $outcome => $label)
                                        <option value="{{ $outcome }}" @selected($call->outcome === $outcome)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="js-inline-save-btn invisible rounded-md bg-slate-700 px-2 py-1 text-xs font-medium text-white" data-row-link-ignore>OK</button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $call->caller?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $call->next_follow_up_at?->format('Y-m-d H:i') ?: '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('calls.show', $call) }}" class="text-slate-700 hover:text-slate-900">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">Zatim zadne hovory.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $calls->links() }}
    </div>
@endsection
