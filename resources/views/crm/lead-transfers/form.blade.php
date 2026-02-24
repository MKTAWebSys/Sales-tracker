@extends('layouts.crm', ['title' => ($leadTransfer->exists ? 'Edit Lead Transfer' : 'New Lead Transfer') . ' | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $leadTransfer->exists ? 'Edit Lead Transfer' : 'New Lead Transfer' }}</h1>
    </div>

    <form method="POST" action="{{ $leadTransfer->exists ? route('lead-transfers.update', $leadTransfer) : route('lead-transfers.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @if ($leadTransfer->exists) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium text-slate-700" for="company_id">Company</label>
            <select id="company_id" name="company_id" required class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Select company</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id', $leadTransfer->company_id) === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            @error('company_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700" for="call_id">Related call (optional)</label>
            <select id="call_id" name="call_id" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">No related call</option>
                @foreach ($calls as $call)
                    <option value="{{ $call->id }}" @selected((string) old('call_id', $leadTransfer->call_id) === (string) $call->id)>
                        #{{ $call->id }} - {{ $call->company?->name ?? '-' }} - {{ $call->called_at?->format('Y-m-d H:i') ?: '-' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700" for="from_user_id">From user</label>
                <select id="from_user_id" name="from_user_id" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Current user</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('from_user_id', $leadTransfer->from_user_id) === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="to_user_id">To user</label>
                <select id="to_user_id" name="to_user_id" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Unassigned</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('to_user_id', $leadTransfer->to_user_id) === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('to_user_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700" for="transferred_at">Transferred at</label>
                <input id="transferred_at" name="transferred_at" type="datetime-local" value="{{ old('transferred_at', optional($leadTransfer->transferred_at)->format('Y-m-d\\TH:i')) }}" required class="mt-1 w-full rounded-md border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="status">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                    @foreach (['pending', 'accepted', 'rejected', 'done'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $leadTransfer->status ?: 'pending') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700" for="note">Note</label>
            <textarea id="note" name="note" rows="5" class="mt-1 w-full rounded-md border-slate-300">{{ old('note', $leadTransfer->note) }}</textarea>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Save</button>
            <a href="{{ route('lead-transfers.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Cancel</a>
        </div>
    </form>
@endsection
