@extends('layouts.crm', ['title' => 'Moje fronta | Call CRM'])

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Moje fronta k obvolani</h1>
            <p class="text-sm text-slate-600">Firmy prirazene mne jako first caller. Razeni FIFO podle casu prirazeni.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('companies.next-mine', ['skip_lost' => 1]) }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Moje dalsi firma</a>
            <a href="{{ route('companies.index', ['mine' => 1]) }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700">Vsechny moje firmy</a>
        </div>
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200">
        Ve fronte je <span class="font-semibold">{{ $companies->total() }}</span> firem.
        Zobrazuji se jen firmy se stavem <code>new</code>, bez prvniho kontaktu a s prirazenym <code>first caller = vy</code>.
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Poradi</th>
                    <th class="px-4 py-3">Firma</th>
                    <th class="px-4 py-3">Owner</th>
                    <th class="px-4 py-3">Queue assigned</th>
                    <th class="px-4 py-3">Vytvoreno</th>
                    <th class="px-4 py-3 text-right">Akce</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($companies as $index => $company)
                    @php
                        $orderNumber = (($companies->currentPage() - 1) * $companies->perPage()) + $index + 1;
                    @endphp
                    <tr class="cursor-pointer hover:bg-slate-50" data-row-link="{{ route('companies.show', $company) }}">
                        <td class="px-4 py-3 font-medium text-slate-700">{{ $orderNumber }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $company->name }}</div>
                            <div class="mt-1 text-xs text-slate-500">ICO: {{ $company->ico ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $company->assignedUser?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $company->first_caller_assigned_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $company->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td class="px-4 py-3 text-right" data-row-link-ignore>
                            <div class="flex justify-end gap-2" data-row-link-ignore>
                                <a href="{{ route('companies.calls.start', $company) }}" class="rounded-md bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white">Zahajit hovor</a>
                                <a href="{{ route('companies.quick-defer', $company) }}" class="rounded-md bg-amber-100 px-2.5 py-1.5 text-xs font-medium text-amber-800 ring-1 ring-amber-200">Odlozit</a>
                                <a href="{{ route('companies.show', $company) }}" class="text-slate-700 hover:text-slate-900">Detail</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">Vase fronta je prazdna.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $companies->links() }}
    </div>
@endsection
