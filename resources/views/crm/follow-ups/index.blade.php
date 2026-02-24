@extends('layouts.crm', ['title' => 'Follow-ups | Call CRM'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Follow-ups</h1>
            <p class="text-sm text-slate-600">Planned callbacks and next steps after calls.</p>
        </div>
        <a href="{{ route('follow-ups.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">New follow-up</a>
    </div>

    <form method="GET" action="{{ route('follow-ups.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-5">
        <div class="md:col-span-2">
            <label for="company_id" class="block text-sm font-medium text-slate-700">Company</label>
            <select id="company_id" name="company_id" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">All companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
            <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">All</option>
                @foreach (['open', 'done', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="due_from" class="block text-sm font-medium text-slate-700">Due from</label>
            <input id="due_from" name="due_from" type="date" value="{{ $filters['due_from'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>
        <div>
            <label for="due_to" class="block text-sm font-medium text-slate-700">Due to</label>
            <input id="due_to" name="due_to" type="date" value="{{ $filters['due_to'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>
        <div class="md:col-span-5 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filter</button>
            <a href="{{ route('follow-ups.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Due at</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Assigned</th>
                    <th class="px-4 py-3">Related call</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($followUps as $followUp)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $followUp->company?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $followUp->due_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $followUp->status }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $followUp->assignedUser?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $followUp->call_id ?: '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('follow-ups.show', $followUp) }}" class="text-slate-700 hover:text-slate-900">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No follow-ups yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $followUps->links() }}</div>
@endsection
