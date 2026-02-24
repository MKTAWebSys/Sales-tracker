@extends('layouts.crm', ['title' => 'Dashboard | Call CRM'])

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-semibold">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-600">Přehled MVP metrik.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Firmy</p><p class="mt-2 text-2xl font-semibold">{{ $stats['companies'] }}</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Hovory</p><p class="mt-2 text-2xl font-semibold">{{ $stats['calls'] }}</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Open follow-upy</p><p class="mt-2 text-2xl font-semibold">{{ $stats['followUpsOpen'] }}</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Předání leadů</p><p class="mt-2 text-2xl font-semibold">{{ $stats['leadTransfers'] }}</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Plánované schůzky</p><p class="mt-2 text-2xl font-semibold">{{ $stats['meetingsPlanned'] }}</p></div>
    </div>

    <div class="mt-8 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-lg font-semibold">MVP další kroky</h2>
        <ul class="mt-3 space-y-2 text-sm text-slate-700">
            <li>Doinstalovat Laravel Breeze (`php artisan breeze:install blade`)</li>
            <li>Napojit CRUD formuláře pro moduly</li>
            <li>Doplnit role/oprávnění a notifikace follow-upů</li>
        </ul>
    </div>
@endsection
