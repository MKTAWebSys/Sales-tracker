@extends('layouts.crm', ['title' => 'Uzivatele | Call CRM'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Uzivatele</h1>
            <p class="text-sm text-slate-600">Sprava uzivatelu (admin/manager).</p>
        </div>
        <a href="{{ route('users.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            Pridat uzivatele
        </a>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Jmeno</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Cil obvolani</th>
                    <th class="px-4 py-3">Termin</th>
                    <th class="px-4 py-3 text-right">Akce</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $userItem)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-4 py-3 font-medium">{{ $userItem->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $userItem->email }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $userItem->role === 'manager' ? 'bg-blue-50 text-blue-800 ring-blue-200' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                {{ $userItem->role }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $userItem->call_target_count ?: '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $userItem->call_target_until?->format('Y-m-d') ?: '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('users.edit', $userItem) }}" class="text-slate-700 hover:text-slate-900">Upravit</a>
                                <form method="POST" action="{{ route('users.destroy', $userItem) }}" onsubmit="return confirm('Smazat uzivatele?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-700 hover:text-rose-900" @disabled(auth()->id() === $userItem->id)>Smazat</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">Zatim zadni uzivatele.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
@endsection
