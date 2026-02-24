@extends('layouts.crm', ['title' => 'Dashboard | Call CRM'])

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-semibold">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-600">MVP overview metrics.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Companies</p><p class="mt-2 text-2xl font-semibold">{{ $stats['companies'] }}</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Calls</p><p class="mt-2 text-2xl font-semibold">{{ $stats['calls'] }}</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Open follow-ups</p><p class="mt-2 text-2xl font-semibold">{{ $stats['followUpsOpen'] }}</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Lead transfers</p><p class="mt-2 text-2xl font-semibold">{{ $stats['leadTransfers'] }}</p></div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs text-slate-500">Meetings planned</p><p class="mt-2 text-2xl font-semibold">{{ $stats['meetingsPlanned'] }}</p></div>
    </div>
@endsection
