@extends('layouts.crm', ['title' => 'Hovory | Call CRM'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Hovory</h1>
            <p class="text-sm text-slate-600">Historie hovoru a jejich vysledky pro firmy.</p>
        </div>
        <a href="{{ route('calls.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            Novy hovor
        </a>
    </div>

    <form method="GET" action="{{ route('calls.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-5">
        <div class="md:col-span-2">
            <label for="company_id" class="block text-sm font-medium text-slate-700">Firma</label>
            <select id="company_id" name="company_id" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Vsechny firmy</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="outcome" class="block text-sm font-medium text-slate-700">Vysledek</label>
            <select id="outcome" name="outcome" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Vse</option>
                @foreach (['pending', 'no-answer', 'callback', 'interested', 'not-interested', 'meeting-booked'] as $outcome)
                    <option value="{{ $outcome }}" @selected(($filters['outcome'] ?? '') === $outcome)>{{ $outcome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="date_from" class="block text-sm font-medium text-slate-700">Datum od</label>
            <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>
        <div>
            <label for="date_to" class="block text-sm font-medium text-slate-700">Datum do</label>
            <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>
        <div>
            <label for="caller_id" class="block text-sm font-medium text-slate-700">ID volajiciho</label>
            <input id="caller_id" name="caller_id" type="number" value="{{ $filters['caller_id'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>
        <div class="flex items-center gap-3 md:col-span-5">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filtrovat</button>
            <a href="{{ route('calls.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Reset</a>
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
                        <td class="px-4 py-3">
                            @include('crm.partials.call-outcome-badge', ['outcome' => $call->outcome])
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
