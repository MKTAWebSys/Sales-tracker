@extends('layouts.crm', ['title' => 'Detail follow-upu | Call CRM'])

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Detail follow-upu</h1>
            <p class="mt-1 text-sm text-slate-600">
                {{ $followUp->company?->name ?? '-' }} | {{ $followUp->due_at?->format('Y-m-d H:i') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('follow-ups.edit', $followUp) }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Upravit</a>
            <a href="{{ route('follow-ups.index') }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700">Zpět</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h2 class="text-lg font-semibold">Poznámka</h2>
            <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $followUp->note ?: 'Bez poznámky.' }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold">Metadata</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-slate-500">Stav</dt><dd>@include('crm.partials.status-badge', ['context' => 'follow-up', 'value' => $followUp->status])</dd></div>
                <div><dt class="text-slate-500">Přiřazeno</dt><dd>{{ $followUp->assignedUser?->name ?? '-' }}</dd></div>
                <div><dt class="text-slate-500">Související hovor</dt><dd>{{ $followUp->call_id ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">Dokončeno</dt><dd>{{ $followUp->completed_at?->format('Y-m-d H:i') ?: '-' }}</dd></div>
            </dl>
        </div>
    </div>
@endsection
