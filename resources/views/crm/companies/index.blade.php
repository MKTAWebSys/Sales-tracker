@extends('layouts.crm', ['title' => 'Companies | Call CRM'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Companies</h1>
            <p class="text-sm text-slate-600">Basic CRM company records for MVP.</p>
        </div>
        <a href="{{ route('companies.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            New company
        </a>
    </div>

    <form method="GET" action="{{ route('companies.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-4">
        <div class="md:col-span-2">
            <label for="q" class="block text-sm font-medium text-slate-700">Search (name / ICO)</label>
            <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
            <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">All</option>
                @foreach (['new', 'contacted', 'follow-up', 'qualified', 'lost'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="assigned_user_id" class="block text-sm font-medium text-slate-700">Owner ID</label>
            <input id="assigned_user_id" name="assigned_user_id" type="number" value="{{ $filters['assigned_user_id'] ?? '' }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>
        <div class="md:col-span-4 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filter</button>
            <a href="{{ route('companies.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">ICO</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Owner</th>
                    <th class="px-4 py-3">Created</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($companies as $company)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $company->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $company->ico ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $company->status }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $company->assignedUser?->name ?? ($company->assigned_user_id ?: '-') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $company->created_at?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('companies.show', $company) }}" class="text-slate-700 hover:text-slate-900">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">No companies yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $companies->links() }}
    </div>
@endsection
