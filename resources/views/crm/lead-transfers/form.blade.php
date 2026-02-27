@extends('layouts.crm', ['title' => ($leadTransfer->exists ? 'Upravit předání leadu' : 'Nové předání leadu') . ' | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $leadTransfer->exists ? 'Upravit předání leadu' : 'Nové předání leadu' }}</h1>
        <p class="text-sm text-slate-600">Předání leadu mezi uživateli a sledování stavu.</p>
    </div>

    <form method="POST" action="{{ $leadTransfer->exists ? route('lead-transfers.update', $leadTransfer) : route('lead-transfers.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @if ($leadTransfer->exists)
            @method('PUT')
        @endif

        <div>
            <label for="company_id" class="block text-sm font-medium text-slate-700">Firma</label>
            <select id="company_id" name="company_id" required class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Vyberte firmu</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id', $leadTransfer->company_id) === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            @error('company_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="call_id" class="block text-sm font-medium text-slate-700">Související hovor (volitelné)</label>
            <select id="call_id" name="call_id" class="mt-1 w-full rounded-md border-slate-300">
                <option value="">Bez souvisejícího hovoru</option>
                @foreach ($calls as $call)
                    <option value="{{ $call->id }}" @selected((string) old('call_id', $leadTransfer->call_id) === (string) $call->id)>
                        #{{ $call->id }} - {{ $call->company?->name ?? '-' }} - {{ $call->called_at?->format('Y-m-d H:i') ?: '-' }}
                    </option>
                @endforeach
            </select>
            @error('call_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="from_user_id" class="block text-sm font-medium text-slate-700">Od uživatele</label>
                <select id="from_user_id" name="from_user_id" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Neznámý / žádný</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('from_user_id', $leadTransfer->from_user_id) === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('from_user_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="to_user_id" class="block text-sm font-medium text-slate-700">Komu</label>
                <select id="to_user_id" name="to_user_id" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Nepřiřazeno</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('to_user_id', $leadTransfer->to_user_id) === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('to_user_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="transferred_at" class="block text-sm font-medium text-slate-700">Datum a čas předání</label>
                <input
                    id="transferred_at"
                    name="transferred_at"
                    type="datetime-local"
                    required
                    value="{{ old('transferred_at', optional($leadTransfer->transferred_at)->format('Y-m-d\\TH:i')) }}"
                    class="sr-only js-datetime-main"
                    data-split-date="transferred_at_date"
                    data-split-time="transferred_at_time"
                >
                <div class="mt-1 flex items-center gap-2 rounded-md bg-white/70 px-2 py-1 ring-1 ring-slate-200">
                    <input id="transferred_at_date" type="date" required class="h-9 rounded-md border-slate-300 text-sm">
                    <input id="transferred_at_time" type="time" required step="60" class="h-9 w-32 rounded-md border-slate-300 text-sm">
                </div>
                @error('transferred_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Stav</label>
                <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                    @foreach (['pending', 'accepted', 'done', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $leadTransfer->status ?: 'pending') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="note" class="block text-sm font-medium text-slate-700">Poznámka</label>
            <textarea id="note" name="note" rows="5" class="mt-1 w-full rounded-md border-slate-300">{{ old('note', $leadTransfer->note) }}</textarea>
            @error('note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Uložit</button>
            <a href="{{ route('lead-transfers.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Zrušit</a>
        </div>
    </form>
@endsection

