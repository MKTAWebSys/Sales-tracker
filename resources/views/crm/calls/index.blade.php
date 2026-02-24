@extends('layouts.crm', ['title' => 'Calls | Call CRM'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Calls</h1>
            <p class="text-sm text-slate-600">Call history and outcomes for contacted companies.</p>
        </div>
        <a href="{{ route('calls.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            New call
        </a>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Called at</th>
                    <th class="px-4 py-3">Outcome</th>
                    <th class="px-4 py-3">Caller</th>
                    <th class="px-4 py-3">Next follow-up</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($calls as $call)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $call->company?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $call->called_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $call->outcome }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $call->caller?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $call->next_follow_up_at?->format('Y-m-d H:i') ?: '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('calls.show', $call) }}" class="text-slate-700 hover:text-slate-900">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">No calls yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $calls->links() }}
    </div>
@endsection
