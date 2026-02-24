@extends('layouts.crm', ['title' => 'Call detail | Call CRM'])

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Call detail</h1>
            <p class="mt-1 text-sm text-slate-600">
                {{ $call->company?->name ?? '-' }} | {{ $call->called_at?->format('Y-m-d H:i') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('calls.edit', $call) }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Edit</a>
            <a href="{{ route('calls.index') }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700">Back</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h2 class="text-lg font-semibold">Summary</h2>
            <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $call->summary ?: 'No summary.' }}</p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold">Metadata</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Outcome</dt>
                    <dd>{{ $call->outcome }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Caller</dt>
                    <dd>{{ $call->caller?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Handed over to</dt>
                    <dd>{{ $call->handedOverTo?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Next follow-up</dt>
                    <dd>{{ $call->next_follow_up_at?->format('Y-m-d H:i') ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Meeting planned</dt>
                    <dd>{{ $call->meeting_planned_at?->format('Y-m-d H:i') ?: '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
