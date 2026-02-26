@php
    $today = now()->toDateString();
    $yesterday = now()->subDay()->toDateString();
    $viewedUserId = $viewedUser?->id;
    $isManagerView = auth()->user()?->isManager();
    $targetCount = $viewedUser?->call_target_count;
    $targetUntil = $viewedUser?->call_target_until;
    $targetRemaining = $targetCount ? max($targetCount - (int) ($myStats['queueCompanies'] ?? 0), 0) : null;
    $userFilterParams = $viewedUserId ? ['assigned_user_id' => $viewedUserId, 'mine' => 0] : [];
    $myCompaniesParams = ($isManagerView && $viewedUserId && auth()->id() !== $viewedUserId)
        ? ['assigned_user_id' => $viewedUserId, 'mine' => 0]
        : ['mine' => 1];
    $myQueueParams = ($isManagerView && $viewedUserId && auth()->id() !== $viewedUserId)
        ? ['first_caller_user_id' => $viewedUserId, 'status' => 'new', 'mine' => 0]
        : [];
    $myFollowUpsOpenParams = ($isManagerView && $viewedUserId && auth()->id() !== $viewedUserId)
        ? ['assigned_user_id' => $viewedUserId, 'mine' => 0, 'status' => 'open']
        : ['mine' => 1, 'status' => 'open'];
    $myFollowUpsOverdueParams = ($isManagerView && $viewedUserId && auth()->id() !== $viewedUserId)
        ? ['assigned_user_id' => $viewedUserId, 'mine' => 0, 'status' => 'open', 'due_to' => $yesterday]
        : ['mine' => 1, 'status' => 'open', 'due_to' => $yesterday];
@endphp

@extends('layouts.crm', ['title' => 'Prehled | Call CRM'])

@section('content')
    <div class="mb-8 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Prehled</h1>
            <p class="mt-1 text-sm text-slate-600">Prehled MVP metrik a priorit follow-upu.</p>
        </div>

        @if (auth()->user()?->isManager())
            <div class="w-full rounded-xl p-4 shadow-sm ring-1 xl:ml-6 xl:w-auto xl:min-w-[28rem] {{ $isViewingOtherUser ? 'bg-amber-50 ring-amber-200' : 'bg-white ring-slate-200' }}">
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-3">
                    <div class="min-w-64 flex-1">
                        <label for="user_view_id" class="block text-sm font-medium {{ $isViewingOtherUser ? 'text-amber-900' : 'text-slate-700' }}">
                            Pohled uzivatele
                        </label>
                        <select id="user_view_id" name="user_view_id" class="mt-1 w-full rounded-md border-slate-300 {{ $isViewingOtherUser ? 'bg-amber-100/60 border-amber-300' : '' }}">
                            @foreach ($dashboardUsers as $userOption)
                                <option value="{{ $userOption->id }}" @selected($viewedUserId === $userOption->id)>{{ $userOption->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Prepnout pohled</button>
                    @if ($isViewingOtherUser)
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-amber-100 px-4 py-2 text-sm font-medium text-amber-900 ring-1 ring-amber-300">Zpet na muj pohled</a>
                        <div class="w-full text-sm text-amber-900">Koukate na dashboard uzivatele: <span class="font-semibold">{{ $viewedUser?->name }}</span></div>
                    @endif
                </form>
            </div>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-7">
        <a href="{{ route('companies.index') }}" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            <p class="text-xs text-slate-500">Firmy</p>
            <p class="mt-2 text-2xl font-semibold">{{ $stats['companies'] }}</p>
        </a>
        <a href="{{ route('calls.index') }}" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            <p class="text-xs text-slate-500">Hovory</p>
            <p class="mt-2 text-2xl font-semibold">{{ $stats['calls'] }}</p>
        </a>
        <a href="{{ route('follow-ups.index', ['mine' => 0, 'status' => 'open']) }}" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            <p class="text-xs text-slate-500">Otevrene follow-upy (globalne)</p>
            <p class="mt-2 text-2xl font-semibold">{{ $stats['followUpsOpen'] }}</p>
        </a>
        <a href="{{ route('follow-ups.index', ['mine' => 0, 'status' => 'open', 'due_from' => $today, 'due_to' => $today]) }}" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-amber-200 transition hover:bg-amber-50/40">
            <p class="text-xs text-amber-700">Na dnes (globalne)</p>
            <p class="mt-2 text-2xl font-semibold text-amber-800">{{ $stats['followUpsDueToday'] }}</p>
        </a>
        <a href="{{ route('follow-ups.index', ['mine' => 0, 'status' => 'open', 'due_to' => $yesterday]) }}" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-rose-200 transition hover:bg-rose-50/40">
            <p class="text-xs text-rose-700">Po terminu (globalne)</p>
            <p class="mt-2 text-2xl font-semibold text-rose-800">{{ $stats['followUpsOverdue'] }}</p>
        </a>
        <a href="{{ route('meetings.index', ['status' => 'planned']) }}" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            <p class="text-xs text-slate-500">Planovane schuzky</p>
            <p class="mt-2 text-2xl font-semibold">{{ $stats['meetingsPlanned'] }}</p>
        </a>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold">{{ $isViewingOtherUser ? 'Fronta uzivatele: '.$viewedUser?->name : 'Moje fronta' }}</h2>
            @if ($targetCount || $targetUntil)
                <div class="mt-3 rounded-lg border {{ $isViewingOtherUser ? 'border-amber-200 bg-amber-50/60' : 'border-blue-200 bg-blue-50/60' }} p-3 text-sm">
                    <div class="font-medium">
                        Cil obvolani:
                        <span>{{ $targetCount ? $targetCount.' firem' : 'neuvedeno' }}</span>
                        @if ($targetUntil)
                            <span>do {{ $targetUntil->format('Y-m-d') }}</span>
                        @endif
                    </div>
                    <div class="mt-1 text-xs text-slate-700">
                        Aktualne ve fronte: {{ $myStats['queueCompanies'] ?? 0 }}
                        @if (!is_null($targetRemaining))
                            | Zbyva k naplneni cile: {{ $targetRemaining }}
                        @endif
                    </div>
                </div>
            @endif
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <a href="{{ $isViewingOtherUser ? route('companies.index', array_merge($myQueueParams, ['mine' => 0])) : route('companies.queue.mine') }}" class="rounded-lg border border-emerald-200 bg-emerald-50/30 p-3 transition hover:bg-emerald-50/50">
                    <div class="text-xs text-emerald-700">{{ $isViewingOtherUser ? 'Fronta uzivatele (new)' : 'Moje fronta k obvolani' }}</div>
                    <div class="mt-1 text-xl font-semibold text-emerald-800">{{ $myStats['queueCompanies'] ?? 0 }}</div>
                </a>
                <a href="{{ route('companies.index', $myCompaniesParams) }}" class="rounded-lg border border-slate-200 p-3 transition hover:bg-slate-50">
                    <div class="text-xs text-slate-500">{{ $isViewingOtherUser ? 'Firmy uzivatele' : 'Moje firmy' }}</div>
                    <div class="mt-1 text-xl font-semibold">{{ $myStats['ownerCompanies'] ?? 0 }}</div>
                    @if ($targetCount || $targetUntil)
                        <div class="mt-1 text-[11px] text-slate-500">
                            {{ $targetCount ? 'cil '.$targetCount : 'cil ?' }}@if($targetUntil) · do {{ $targetUntil->format('Y-m-d') }}@endif
                        </div>
                    @endif
                </a>
                <a href="{{ route('follow-ups.index', $myFollowUpsOpenParams) }}" class="rounded-lg border border-slate-200 p-3 transition hover:bg-slate-50">
                    <div class="text-xs text-slate-500">{{ $isViewingOtherUser ? 'Otevrene follow-upy uzivatele' : 'Moje otevrene follow-upy' }}</div>
                    <div class="mt-1 text-xl font-semibold">{{ $myStats['followUpsOpen'] ?? 0 }}</div>
                </a>
                <a href="{{ route('follow-ups.index', $myFollowUpsOverdueParams) }}" class="rounded-lg border border-rose-200 p-3 transition hover:bg-rose-50/40">
                    <div class="text-xs text-rose-700">{{ $isViewingOtherUser ? 'Po terminu (uzivatel)' : 'Moje po terminu' }}</div>
                    <div class="mt-1 text-xl font-semibold text-rose-800">{{ $myStats['followUpsOverdue'] ?? 0 }}</div>
                </a>
            </div>

            <div class="mt-4 flex items-center gap-3 text-sm">
                @if (! $isViewingOtherUser)
                    <a href="{{ route('companies.queue.mine') }}" class="text-emerald-700 underline">Moje fronta k obvolani</a>
                @endif
                <a href="{{ route('companies.index', $myCompaniesParams) }}" class="text-slate-700 underline">{{ $isViewingOtherUser ? 'Firmy uzivatele' : 'Moje firmy' }}</a>
                <a href="{{ route('follow-ups.index', $myFollowUpsOpenParams) }}" class="text-slate-700 underline">{{ $isViewingOtherUser ? 'Otevrene follow-upy uzivatele' : 'Moje otevrene follow-upy' }}</a>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($myFollowUpsList as $followUp)
                    <li class="rounded-lg border border-slate-100 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-medium">{{ $followUp->company?->name ?? '-' }}</div>
                            <a href="{{ route('follow-ups.show', $followUp) }}" class="text-xs text-slate-600 hover:text-slate-900">Detail</a>
                        </div>
                        <div class="mt-1 text-slate-500">{{ $followUp->due_at?->format('Y-m-d H:i') ?: '-' }}</div>
                    </li>
                @empty
                    <li class="text-slate-500">{{ $isViewingOtherUser ? 'Vybrany uzivatel nema zadne otevrene follow-upy.' : 'Nemate zadne otevrene follow-upy.' }}</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">{{ $isViewingOtherUser ? 'Follow-upy dnes (uzivatel)' : 'Follow-upy dnes' }}</h2>
                <a href="{{ route('follow-ups.index', array_merge($userFilterParams, ['status' => 'open', 'due_from' => $today, 'due_to' => $today])) }}" class="text-sm text-slate-600 hover:text-slate-900">Otevrit filtr</a>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($followUpsDueTodayList as $followUp)
                    <li class="rounded-lg border border-slate-100 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-medium">{{ $followUp->company?->name ?? '-' }}</div>
                            <a href="{{ route('follow-ups.show', $followUp) }}" class="text-xs text-slate-600 hover:text-slate-900">Detail</a>
                        </div>
                        <div class="mt-1 text-slate-500">{{ $followUp->due_at?->format('Y-m-d H:i') ?: '-' }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $followUp->assignedUser?->name ? 'Prirazeno: '.$followUp->assignedUser->name : 'Neprirazeno' }}</div>
                    </li>
                @empty
                    <li class="text-slate-500">{{ $isViewingOtherUser ? 'Nic na dnes pro vybraneho uzivatele.' : 'Nic na dnesek.' }}</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">{{ $isViewingOtherUser ? 'Po terminu (uzivatel)' : 'Po terminu' }}</h2>
                <a href="{{ route('follow-ups.index', array_merge($userFilterParams, ['status' => 'open', 'due_to' => $yesterday])) }}" class="text-sm text-slate-600 hover:text-slate-900">Otevrit filtr</a>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($followUpsOverdueList as $followUp)
                    <li class="rounded-lg border border-rose-100 bg-rose-50/30 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-medium">{{ $followUp->company?->name ?? '-' }}</div>
                            <a href="{{ route('follow-ups.show', $followUp) }}" class="text-xs text-slate-600 hover:text-slate-900">Detail</a>
                        </div>
                        <div class="mt-1 text-rose-700">{{ $followUp->due_at?->format('Y-m-d H:i') ?: '-' }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $followUp->assignedUser?->name ? 'Prirazeno: '.$followUp->assignedUser->name : 'Neprirazeno' }}</div>
                    </li>
                @empty
                    <li class="text-slate-500">{{ $isViewingOtherUser ? 'Nic po terminu pro vybraneho uzivatele.' : 'Nic po terminu.' }}</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-8 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-lg font-semibold">Dalsi MVP kroky</h2>
        <ul class="mt-3 space-y-2 text-sm text-slate-700">
            <li>Doladit filtry a hromadne workflow pro follow-upy a predani leadu</li>
            <li>Doplnit mazani s potvrzenim</li>
            <li>Rozsirit testy pro CRM moduly</li>
        </ul>
    </div>
@endsection
