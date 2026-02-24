@extends('layouts.crm', ['title' => 'Meetings | Call CRM'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Meetings</h1>
            <p class="text-sm text-slate-600">Scheduled meetings and sales follow-up appointments.</p>
        </div>
        <a href="{{ route('meetings.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">New meeting</a>
    </div>

    <form method="GET" action="{{ route('meetings.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-5">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700" for="company_id">Company</label>
            <select id="company_id" name="company_id" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">All companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700" for="mode">Mode</label>
            <select id="mode" name="mode" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">All</option>
                @foreach (['onsite', 'online', 'phone'] as $mode)
                    <option value="{{ $mode }}" @selected(($filters['mode'] ?? '') === $mode)>{{ $mode }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700" for="status">Status</label>
            <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">All</option>
                @foreach (['planned', 'confirmed', 'done', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filter</button>
            <a href="{{ route('meetings.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Scheduled at</th>
                    <th class="px-4 py-3">Mode</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Call</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($meetings as $meeting)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $meeting->company?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $meeting->scheduled_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $meeting->mode }}</td>
                        <td class="px-4 py-3">{{ $meeting->status }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $meeting->call_id ?: '-' }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('meetings.show', $meeting) }}" class="text-slate-700 hover:text-slate-900">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No meetings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $meetings->links() }}</div>
@endsection
