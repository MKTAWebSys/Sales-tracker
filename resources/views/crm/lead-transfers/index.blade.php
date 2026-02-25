@extends('layouts.crm', ['title' => 'Předání leadů | Call CRM'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Předání leadů</h1>
            <p class="text-sm text-slate-600">Předání mezi obchodníky/telefonisty po hovoru.</p>
        </div>
        <a href="{{ route('lead-transfers.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Nové předání</a>
    </div>

    <form method="GET" action="{{ route('lead-transfers.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-6">
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
                @foreach (['pending', 'accepted', 'done', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="call_id" class="block text-sm font-medium text-slate-700">ID hovoru</label>
            <input id="call_id" name="call_id" type="number" value="{{ $filters['call_id'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>
        <div>
            <label for="from_user_id" class="block text-sm font-medium text-slate-700">Od uživatele</label>
            <select id="from_user_id" name="from_user_id" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Všichni</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(($filters['from_user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="to_user_id" class="block text-sm font-medium text-slate-700">Komu</label>
            <select id="to_user_id" name="to_user_id" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Všichni</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(($filters['to_user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-6 flex items-end gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filtrovat</button>
            <a href="{{ route('lead-transfers.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Firma</th>
                    <th class="px-4 py-3">Předáno</th>
                    <th class="px-4 py-3">Od</th>
                    <th class="px-4 py-3">Komu</th>
                    <th class="px-4 py-3">Stav</th>
                    <th class="px-4 py-3">Hovor</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($leadTransfers as $leadTransfer)
                    @php
                        $rowClass = match ($leadTransfer->status) {
                            'done' => 'bg-emerald-50/50',
                            'accepted' => 'bg-blue-50/60',
                            'pending' => 'bg-amber-50/60',
                            'cancelled' => 'bg-slate-50/70',
                            default => '',
                        };
                    @endphp
                    <tr class="{{ $rowClass }} cursor-pointer hover:brightness-[0.99]" data-row-link="{{ route('lead-transfers.show', $leadTransfer) }}">
                        <td class="px-4 py-3 font-medium">{{ $leadTransfer->company?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $leadTransfer->transferred_at?->format('Y-m-d H:i') ?: '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $leadTransfer->fromUser?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $leadTransfer->toUser?->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @include('crm.partials.status-badge', ['context' => 'lead-transfer', 'value' => $leadTransfer->status])
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($leadTransfer->call_id)
                                <a href="{{ route('calls.show', $leadTransfer->call_id) }}" class="underline">#{{ $leadTransfer->call_id }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('lead-transfers.show', $leadTransfer) }}" class="text-slate-700 hover:text-slate-900">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Zatím žádná předání leadů.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $leadTransfers->links() }}</div>
@endsection
