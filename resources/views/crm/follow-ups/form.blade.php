@extends('layouts.crm', ['title' => ($followUp->exists ? 'Upravit follow-up' : 'Nový follow-up') . ' | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $followUp->exists ? 'Upravit follow-up' : 'Nový follow-up' }}</h1>
        <p class="text-sm text-slate-600">Plánování dalšího kontaktu (follow-up) pro MVP.</p>
    </div>

    <form method="POST" action="{{ $followUp->exists ? route('follow-ups.update', $followUp) : route('follow-ups.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @if ($followUp->exists)
            @method('PUT')
        @endif

        <div>
            <label for="company_id" class="block text-sm font-medium text-slate-700">Firma</label>
            <select id="company_id" name="company_id" required class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Vyberte firmu</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id', $followUp->company_id) === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            @error('company_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="call_id" class="block text-sm font-medium text-slate-700">Související hovor (volitelné)</label>
            <select id="call_id" name="call_id" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Bez souvisejícího hovoru</option>
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
                <label for="due_at" class="block text-sm font-medium text-slate-700">Termín</label>
                <input id="due_at" name="due_at" type="datetime-local" required value="{{ old('due_at', optional($followUp->due_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                @error('due_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Stav</label>
                <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                    @foreach (['open', 'done', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $followUp->status ?: 'open') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        @if (auth()->user()?->isManager())
            <div>
                <label for="assigned_user_id" class="block text-sm font-medium text-slate-700">Přiřazený uživatel</label>
                <select id="assigned_user_id" name="assigned_user_id" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Nepřiřazeno</option>
                    @foreach (($users ?? collect()) as $user)
                        <option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $followUp->assigned_user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->role ?? 'caller' }})</option>
                    @endforeach
                </select>
                @error('assigned_user_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label for="note" class="block text-sm font-medium text-slate-700">Poznámka</label>
            <textarea id="note" name="note" rows="5" class="mt-1 w-full rounded-md border-slate-300">{{ old('note', $followUp->note) }}</textarea>
            @error('note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Uložit</button>
            <a href="{{ route('follow-ups.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Zrušit</a>
        </div>
    </form>
@endsection
