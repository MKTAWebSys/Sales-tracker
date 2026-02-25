@extends('layouts.crm', ['title' => ($company->exists ? 'Upravit firmu' : 'Nova firma') . ' | Call CRM'])

@section('content')
    @php
        $isManager = auth()->user()?->isManager() ?? false;
        $firstCallerAssignedAtValue = old('first_caller_assigned_at', optional($company->first_caller_assigned_at)->format('Y-m-d\\TH:i'));
        $firstContactedAtValue = old('first_contacted_at', optional($company->first_contacted_at)->format('Y-m-d\\TH:i'));
    @endphp

    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $company->exists ? 'Upravit firmu' : 'Nova firma' }}</h1>
        <p class="text-sm text-slate-600">Formular firmy pro MVP. Owner a first caller queue jsou oddelene.</p>
    </div>

    <form method="POST" action="{{ $company->exists ? route('companies.update', $company) : route('companies.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @if ($company->exists)
            @method('PUT')
        @endif

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Nazev</label>
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

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="contact_person" class="block text-sm font-medium text-slate-700">Kontaktni osoba</label>
                <input id="contact_person" name="contact_person" type="text" value="{{ old('contact_person', $company->contact_person) }}" class="mt-1 w-full rounded-md border-slate-300">
                @error('contact_person')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">Telefon</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $company->phone) }}" class="mt-1 w-full rounded-md border-slate-300" placeholder="+420...">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @if ($isManager)
            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Prirazeni a call queue</h2>
                <p class="mt-1 text-xs text-slate-500">Owner = obchodnik / vlastnik kontaktu. First caller = kdo dela prvni osloveni.</p>

                <div class="mt-4 grid gap-6 lg:grid-cols-2">
                    <div>
                        <label for="assigned_user_id" class="block text-sm font-medium text-slate-700">Owner (assigned user)</label>
                        <select id="assigned_user_id" name="assigned_user_id" class="mt-1 w-full rounded-md border-slate-300">
                            <option value="">Neprirazeno</option>
                            @foreach (($users ?? collect()) as $user)
                                <option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $company->assigned_user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->role ?? 'caller' }})</option>
                            @endforeach
                        </select>
                        @error('assigned_user_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="first_caller_user_id" class="block text-sm font-medium text-slate-700">First caller (prvni osloveni)</label>
                        <select id="first_caller_user_id" name="first_caller_user_id" class="mt-1 w-full rounded-md border-slate-300">
                            <option value="">Neprirazeno</option>
                            @foreach (($users ?? collect()) as $user)
                                <option value="{{ $user->id }}" @selected((string) old('first_caller_user_id', $company->first_caller_user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->role ?? 'caller' }})</option>
                            @endforeach
                        </select>
                        @error('first_caller_user_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="first_caller_assigned_at" class="block text-sm font-medium text-slate-700">Queue assigned at</label>
                        <input id="first_caller_assigned_at" name="first_caller_assigned_at" type="datetime-local" value="{{ $firstCallerAssignedAtValue }}" class="mt-1 w-full rounded-md border-slate-300">
                        @error('first_caller_assigned_at')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="first_contacted_at" class="block text-sm font-medium text-slate-700">First contacted at</label>
                        <input id="first_contacted_at" name="first_contacted_at" type="datetime-local" value="{{ $firstContactedAtValue }}" class="mt-1 w-full rounded-md border-slate-300">
                        @error('first_contacted_at')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        @endif

        <div>
            <label for="notes" class="block text-sm font-medium text-slate-700">Poznamky</label>
            <textarea id="notes" name="notes" rows="5" class="mt-1 w-full rounded-md border-slate-300">{{ old('notes', $company->notes) }}</textarea>
            @error('notes')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Ulozit</button>
            <a href="{{ route('companies.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Zrusit</a>
        </div>
    </form>
@endsection
