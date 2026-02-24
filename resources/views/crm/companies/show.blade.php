@extends('layouts.crm', ['title' => $company->name . ' | Call CRM'])

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ $company->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">
                Status: {{ $company->status }} | Owner: {{ $company->assignedUser?->name ?? '-' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('calls.create', ['company_id' => $company->id]) }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">New call</a>
            <a href="{{ route('companies.edit', $company) }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Edit</a>
            <a href="{{ route('companies.index') }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700">Back</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h2 class="text-lg font-semibold">Company details</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-slate-500">Name</dt>
                    <dd class="font-medium">{{ $company->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">ICO</dt>
                    <dd class="font-medium">{{ $company->ico ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Website</dt>
                    <dd class="font-medium">
                        @if ($company->website)
                            <a href="{{ $company->website }}" target="_blank" rel="noreferrer" class="text-slate-700 underline">
                                {{ $company->website }}
                            </a>
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Created</dt>
                    <dd class="font-medium">{{ $company->created_at?->format('Y-m-d H:i') }}</dd>
                </div>
            </dl>

            <div class="mt-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Notes</h3>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $company->notes ?: 'No notes.' }}</p>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Call timeline</h2>
                <a href="{{ route('calls.index') }}" class="text-sm text-slate-600 hover:text-slate-900">All calls</a>
            </div>
            <ul class="mt-4 space-y-3 text-sm text-slate-700">
                @forelse ($company->calls as $call)
                    <li class="rounded-lg border border-slate-100 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-medium">{{ $call->outcome }}</div>
                            <a href="{{ route('calls.show', $call) }}" class="text-xs text-slate-600 hover:text-slate-900">Detail</a>
                        </div>
                        <div class="mt-1 text-slate-500">{{ $call->called_at?->format('Y-m-d H:i') ?: '-' }}</div>
                        @if ($call->summary)
                            <p class="mt-2 line-clamp-3 text-slate-700">{{ $call->summary }}</p>
                        @endif
                        @if ($call->next_follow_up_at)
                            <div class="mt-2 text-xs text-amber-700">
                                Next follow-up: {{ $call->next_follow_up_at->format('Y-m-d H:i') }}
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="text-slate-500">No calls yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
