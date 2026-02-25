@extends('layouts.crm', ['title' => 'Detail hovoru | Call CRM'])

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Detail hovoru</h1>
            <p class="mt-1 text-sm text-slate-600">
                {{ $call->company?->name ?? '-' }} | {{ $call->called_at?->format('Y-m-d H:i') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if ($call->outcome === 'pending')
                <a href="{{ route('calls.finish', $call) }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Ukoncit hovor</a>
            @endif
            <a href="{{ route('calls.edit', $call) }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Upravit</a>
            <a href="{{ route('calls.index') }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700">Zpet</a>
        </div>
    </div>

    @if ($call->outcome === 'pending')
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Tento hovor je zatim rozpracovany. Klikni na <span class="font-medium">Ukoncit hovor</span> a dopln vysledek + navazujici kroky.
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h2 class="text-lg font-semibold">Shrnuti</h2>
            <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $call->summary ?: 'Bez shrnuti.' }}</p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold">Metadata</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Vysledek</dt>
                    <dd>@include('crm.partials.call-outcome-badge', ['outcome' => $call->outcome])</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Volal</dt>
                    <dd>{{ $call->caller?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Predano komu</dt>
                    <dd>{{ $call->handedOverTo?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Dalsi follow-up</dt>
                    <dd>{{ $call->next_follow_up_at?->format('Y-m-d H:i') ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Planovana schuzka</dt>
                    <dd>{{ $call->meeting_planned_at?->format('Y-m-d H:i') ?: '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Navazujici kroky</h2>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('follow-ups.create', ['company_id' => $call->company_id, 'call_id' => $call->id]) }}" class="text-slate-700 underline">
                    Vytvorit follow-up
                </a>
                <a href="{{ route('lead-transfers.create', ['company_id' => $call->company_id, 'call_id' => $call->id]) }}" class="text-slate-700 underline">
                    Vytvorit predani
                </a>
                <a href="{{ route('meetings.create', ['company_id' => $call->company_id, 'call_id' => $call->id]) }}" class="text-slate-700 underline">
                    Vytvorit schuzku
                </a>
            </div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-3 text-sm">
            <div class="rounded-lg border border-slate-200 p-3">
                <div class="text-slate-500">Navazane follow-upy</div>
                <div class="mt-1 text-lg font-semibold">{{ $call->follow_ups_count }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 p-3">
                <div class="text-slate-500">Navazana predani</div>
                <div class="mt-1 text-lg font-semibold">{{ $call->lead_transfers_count }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 p-3">
                <div class="text-slate-500">Navazane schuzky</div>
                <div class="mt-1 text-lg font-semibold">{{ $call->meetings_count }}</div>
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-500">
            Follow-up, predani a schuzka se automaticky vytvori podle vyplnenych poli v hovoru.
        </p>
    </div>
@endsection
