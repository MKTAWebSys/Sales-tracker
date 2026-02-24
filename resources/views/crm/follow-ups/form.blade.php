@extends('layouts.crm', ['title' => ($followUp->exists ? 'Edit follow-up' : 'New follow-up') . ' | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $followUp->exists ? 'Edit follow-up' : 'New follow-up' }}</h1>
        <p class="text-sm text-slate-600">MVP follow-up planner for next contact date.</p>
    </div>

    <form method="POST" action="{{ $followUp->exists ? route('follow-ups.update', $followUp) : route('follow-ups.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @if ($followUp->exists)
            @method('PUT')
        @endif

        <div>
            <label for="company_id" class="block text-sm font-medium text-slate-700">Company</label>
            <select id="company_id" name="company_id" required class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Select company</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id', $followUp->company_id) === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            @error('company_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="call_id" class="block text-sm font-medium text-slate-700">Related call (optional)</label>
            <select id="call_id" name="call_id" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">No related call</option>
                @foreach ($calls as $call)
                    <option value="{{ $call->id }}" @selected((string) old('call_id', $followUp->call_id) === (string) $call->id)>
                        #{{ $call->id }} - {{ $call->company?->name ?? '-' }} - {{ $call->called_at?->format('Y-m-d H:i') ?: '-' }}
                    </option>
                @endforeach
            </select>
            @error('call_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="due_at" class="block text-sm font-medium text-slate-700">Due at</label>
                <input id="due_at" name="due_at" type="datetime-local" required value="{{ old('due_at', optional($followUp->due_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                @error('due_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                    @foreach (['open', 'done', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $followUp->status ?: 'open') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="note" class="block text-sm font-medium text-slate-700">Note</label>
            <textarea id="note" name="note" rows="5" class="mt-1 w-full rounded-md border-slate-300">{{ old('note', $followUp->note) }}</textarea>
            @error('note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Save</button>
            <a href="{{ route('follow-ups.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Cancel</a>
        </div>
    </form>
@endsection
