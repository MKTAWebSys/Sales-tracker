@extends('layouts.crm', ['title' => 'Schůzky | Call CRM'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Schůzky</h1>
            <p class="text-sm text-slate-600">Plánované schůzky a obchodní jednání.</p>
        </div>
        <a href="{{ route('meetings.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Nová schůzka</a>
    </div>

    <form method="GET" action="{{ route('meetings.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-5">
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
                @foreach (['planned', 'confirmed', 'done', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="call_id" class="block text-sm font-medium text-slate-700">ID hovoru</label>
            <input id="call_id" name="call_id" type="number" value="{{ $filters['call_id'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>
        <div class="md:col-span-5 flex items-end gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filtrovat</button>
            <a href="{{ route('meetings.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Firma</th>
                    <th class="px-4 py-3">Termín</th>
                    <th class="px-4 py-3">Forma</th>
                    <th class="px-4 py-3">Stav</th>
                    <th class="px-4 py-3">Hovor</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($meetings as $meeting)
                    @php
                        $rowClass = match ($meeting->status) {
                            'confirmed' => 'bg-emerald-50/60',
                            'planned' => 'bg-blue-50/60',
                            'cancelled' => 'bg-rose-50/50',
                            'done' => 'bg-slate-50/70',
                            default => '',
                        };
                    @endphp
                    <tr class="{{ $rowClass }} cursor-pointer hover:brightness-[0.99]" data-row-link="{{ route('meetings.show', $meeting) }}">
                        <td class="px-4 py-3 font-medium">{{ $meeting->company?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $meeting->scheduled_at?->format('Y-m-d H:i') ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $meeting->mode }}</td>
                        <td class="px-4 py-3">
                            @include('crm.partials.status-badge', ['context' => 'meeting', 'value' => $meeting->status])
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($meeting->call_id)
                                <a href="{{ route('calls.show', $meeting->call_id) }}" class="underline">#{{ $meeting->call_id }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('meetings.show', $meeting) }}" class="text-slate-700 hover:text-slate-900">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Zatím žádné schůzky.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $meetings->links() }}</div>
@endsection
