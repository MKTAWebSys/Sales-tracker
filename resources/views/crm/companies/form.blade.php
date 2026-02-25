@extends('layouts.crm', ['title' => ($company->exists ? 'Upravit firmu' : 'Nová firma') . ' | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $company->exists ? 'Upravit firmu' : 'Nová firma' }}</h1>
        <p class="text-sm text-slate-600">Formulář firmy pro MVP se základní validací.</p>
    </div>

    <form method="POST" action="{{ $company->exists ? route('companies.update', $company) : route('companies.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @if ($company->exists)
            @method('PUT')
        @endif

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Název</label>
            <input id="name" name="name" type="text" value="{{ old('name', $company->name) }}" required class="mt-1 w-full rounded-md border-slate-300">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="ico" class="block text-sm font-medium text-slate-700">ICO</label>
                <input id="ico" name="ico" type="text" value="{{ old('ico', $company->ico) }}" class="mt-1 w-full rounded-md border-slate-300">
                @error('ico')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Stav</label>
                <select id="status" name="status" class="mt-1 w-full rounded-md border-slate-300">
                    @foreach (['new', 'contacted', 'follow-up', 'qualified', 'lost'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $company->status ?: 'new') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="website" class="block text-sm font-medium text-slate-700">Web</label>
            <input id="website" name="website" type="url" value="{{ old('website', $company->website) }}" class="mt-1 w-full rounded-md border-slate-300">
            @error('website')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        @if (auth()->user()?->isManager())
            <div>
                <label for="assigned_user_id" class="block text-sm font-medium text-slate-700">Přiřazený uživatel</label>
                <select id="assigned_user_id" name="assigned_user_id" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Nepřiřazeno</option>
                    @foreach (($users ?? collect()) as $user)
                        <option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $company->assigned_user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->role ?? 'caller' }})</option>
                    @endforeach
                </select>
                @error('assigned_user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <div>
            <label for="notes" class="block text-sm font-medium text-slate-700">Poznámky</label>
            <textarea id="notes" name="notes" rows="5" class="mt-1 w-full rounded-md border-slate-300">{{ old('notes', $company->notes) }}</textarea>
            @error('notes')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Uložit</button>
            <a href="{{ route('companies.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Zrušit</a>
        </div>
    </form>
@endsection
