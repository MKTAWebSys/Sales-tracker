@php
    $isEdit = $userRecord->exists;
@endphp

@extends('layouts.crm', ['title' => ($isEdit ? 'Upravit uzivatele' : 'Pridat uzivatele') . ' | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $isEdit ? 'Upravit uzivatele' : 'Pridat uzivatele' }}</h1>
        <p class="text-sm text-slate-600">Admin sprava uzivatelskych uctu.</p>
    </div>

    <form method="POST" action="{{ $isEdit ? route('users.update', $userRecord) : route('users.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Jmeno</label>
                <input id="name" name="name" type="text" value="{{ old('name', $userRecord->name) }}" required class="mt-1 w-full rounded-md border-slate-300">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $userRecord->email) }}" required class="mt-1 w-full rounded-md border-slate-300">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                <select id="role" name="role" class="mt-1 w-full rounded-md border-slate-300">
                    @foreach (['caller', 'manager'] as $role)
                        <option value="{{ $role }}" @selected(old('role', $userRecord->role ?: 'caller') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
                @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="call_target_count" class="block text-sm font-medium text-slate-700">Cil obvolani</label>
                    <input id="call_target_count" name="call_target_count" type="number" min="1" value="{{ old('call_target_count', $userRecord->call_target_count) }}" class="mt-1 w-full rounded-md border-slate-300">
                    @error('call_target_count') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="call_target_until" class="block text-sm font-medium text-slate-700">Termin do</label>
                    <input id="call_target_until" name="call_target_until" type="date" value="{{ old('call_target_until', $userRecord->call_target_until?->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-slate-300">
                    @error('call_target_until') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Heslo {{ $isEdit ? '(nechat prazdne = beze zmeny)' : '' }}</label>
                <input id="password" name="password" type="password" {{ $isEdit ? '' : 'required' }} class="mt-1 w-full rounded-md border-slate-300">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Potvrzeni hesla</label>
                <input id="password_confirmation" name="password_confirmation" type="password" {{ $isEdit ? '' : 'required' }} class="mt-1 w-full rounded-md border-slate-300">
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Ulozit</button>
            <a href="{{ route('users.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Zrusit</a>
        </div>
    </form>
@endsection
