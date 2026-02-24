@extends('layouts.crm', ['title' => ($meeting->exists ? 'Edit Meeting' : 'New Meeting') . ' | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $meeting->exists ? 'Edit Meeting' : 'New Meeting' }}</h1>
    </div>

    <form method="POST" action="{{ $meeting->exists ? route('meetings.update', $meeting) : route('meetings.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @if ($meeting->exists) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium text-slate-700" for="company_id">Company</label>
            <select id="company_id" name="company_id" required class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Select company</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id', $meeting->company_id) === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            @error('company_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700" for="call_id">Related call (optional)</label>
            <select id="call_id" name="call_id" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">No related call</option>
                @foreach ($calls as $call)
                    <option value="{{ $call->id }}" @selected((string) old('call_id', $meeting->call_id) === (string) $call->id)>
                        #{{ $call->id }} - {{ $call->company?->name ?? '-' }} - {{ $call->called_at?->format('Y-m-d H:i') ?: '-' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid gap-6 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700" for="scheduled_at">Scheduled at</label>
                <input id="scheduled_at" name="scheduled_at" type="datetime-local" required value="{{ old('scheduled_at', optional($meeting->scheduled_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="mode">Mode</label>
                <select id="mode" name="mode" class="mt-1 w-full rounded-md border-slate-300">
                    @foreach (['onsite', 'online', 'phone'] as $mode)
                        <option value="{{ $mode }}" @selected(old('mode', $meeting->mode ?: 'online') === $mode)>{{ $mode }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="status">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                    @foreach (['planned', 'confirmed', 'done', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $meeting->status ?: 'planned') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700" for="note">Note</label>
            <textarea id="note" name="note" rows="5" class="mt-1 w-full rounded-md border-slate-300">{{ old('note', $meeting->note) }}</textarea>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Save</button>
            <a href="{{ route('meetings.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Cancel</a>
        </div>
    </form>
@endsection
