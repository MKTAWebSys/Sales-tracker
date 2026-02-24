@extends('layouts.crm', ['title' => ($call->exists ? 'Edit call' : 'New call') . ' | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $call->exists ? 'Edit call' : 'New call' }}</h1>
        <p class="text-sm text-slate-600">MVP call entry with timeline-relevant fields.</p>
    </div>

    <form method="POST" action="{{ $call->exists ? route('calls.update', $call) : route('calls.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @if ($call->exists)
            @method('PUT')
        @endif

        <div>
            <label for="company_id" class="block text-sm font-medium text-slate-700">Company</label>
            <select id="company_id" name="company_id" required class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Select company</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id', $call->company_id) === (string) $company->id)>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
            @error('company_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="called_at" class="block text-sm font-medium text-slate-700">Called at</label>
                <input id="called_at" name="called_at" type="datetime-local" required value="{{ old('called_at', optional($call->called_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                @error('called_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="outcome" class="block text-sm font-medium text-slate-700">Outcome</label>
                <select id="outcome" name="outcome" class="mt-1 w-full rounded-md border-slate-300">
                    @foreach (['no-answer', 'callback', 'interested', 'not-interested', 'meeting-booked'] as $outcome)
                        <option value="{{ $outcome }}" @selected(old('outcome', $call->outcome ?: 'callback') === $outcome)>{{ $outcome }}</option>
                    @endforeach
                </select>
                @error('outcome')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="next_follow_up_at" class="block text-sm font-medium text-slate-700">Next follow-up at</label>
                <input id="next_follow_up_at" name="next_follow_up_at" type="datetime-local" value="{{ old('next_follow_up_at', optional($call->next_follow_up_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                @error('next_follow_up_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="meeting_planned_at" class="block text-sm font-medium text-slate-700">Meeting planned at</label>
                <input id="meeting_planned_at" name="meeting_planned_at" type="datetime-local" value="{{ old('meeting_planned_at', optional($call->meeting_planned_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                @error('meeting_planned_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="summary" class="block text-sm font-medium text-slate-700">Summary</label>
            <textarea id="summary" name="summary" rows="6" class="mt-1 w-full rounded-md border-slate-300">{{ old('summary', $call->summary) }}</textarea>
            @error('summary')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Save</button>
            <a href="{{ route('calls.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Cancel</a>
        </div>
    </form>
@endsection
