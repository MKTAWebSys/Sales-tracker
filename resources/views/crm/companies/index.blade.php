@extends('layouts.crm', ['title' => 'Firmy | Call CRM'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Firmy</h1>
            <p class="text-sm text-slate-600">Zakladni CRM evidence firem pro MVP.</p>
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
                    · do {{ $quotaUser->call_target_until->format('Y-m-d') }}
                @endif
            </div>
            <div class="mt-1 text-xs text-blue-800/80">
                Akt. zobrazeno ve filtru: {{ $companies->total() }} firem
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('companies.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-4">
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
        @if (auth()->user()?->isManager())
            <div>
                <label for="assigned_user_id" class="block text-sm font-medium text-slate-700">ID vlastnika</label>
                <input id="assigned_user_id" name="assigned_user_id" type="number" value="{{ $filters['assigned_user_id'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
            </div>
        @endif
        <div class="flex flex-wrap items-center gap-3 md:col-span-4">
            @if (auth()->user()?->isManager())
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="mine" value="0">
                    <input type="checkbox" name="mine" value="1" class="rounded border-slate-300" @checked(($filters['mine'] ?? '1') === '1')>
                    <span>Moje firmy (vychozi)</span>
                </label>
            @else
                <input type="hidden" name="mine" value="1">
                <span class="text-xs text-slate-500">Zobrazeny jsou vase prirazene firmy.</span>
            @endif
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filtrovat</button>
            <a href="{{ route('companies.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nazev</th>
                    <th class="px-4 py-3">ICO</th>
                    <th class="px-4 py-3">Stav</th>
                    <th class="px-4 py-3">Vlastnik</th>
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
                    @endphp
                    <tr class="{{ $rowClass }} cursor-pointer hover:brightness-[0.99]" data-row-link="{{ route('companies.show', $company) }}">
                        <td class="px-4 py-3 font-medium">{{ $company->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $company->ico ?: '-' }}</td>
                        <td class="px-4 py-3" data-row-link-ignore>
                            <form method="POST" action="{{ route('companies.quick-status', $company) }}" class="flex items-center gap-2" data-row-link-ignore>
                                @csrf
                                <select name="status" class="min-w-36 rounded-md py-1 text-xs {{ $statusSelectClass }}" data-row-link-ignore>
                                    @foreach (['new', 'contacted', 'follow-up', 'qualified', 'lost'] as $statusOption)
                                        <option value="{{ $statusOption }}" @selected($company->status === $statusOption)>{{ $statusOption }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded-md bg-slate-700 px-2 py-1 text-xs font-medium text-white" data-row-link-ignore>
                                    OK
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $company->assignedUser?->name ?? ($company->assigned_user_id ?: '-') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $company->created_at?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-right" data-row-link-ignore>
                            <div class="flex justify-end gap-2" data-row-link-ignore>
                                <a href="{{ route('companies.calls.start', $company) }}" class="rounded-md bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white">
                                    Zahajit hovor
                                </a>
                                <a href="{{ route('companies.show', $company) }}" class="text-slate-700 hover:text-slate-900">Detail</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">Zatim zadne firmy.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $companies->links() }}
    </div>
@endsection
