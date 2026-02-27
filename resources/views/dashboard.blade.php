@php
    $today = now()->toDateString();
    $viewedUserId = $viewedUser?->id;
    $isManagerView = auth()->user()?->isManager();
    $targetCount = $viewedUser?->call_target_count;
    $targetUntil = $viewedUser?->call_target_until;
    $userFilterParams = $viewedUserId ? ['assigned_user_id' => $viewedUserId, 'mine' => 0] : [];
    $myCompaniesParams = ($isManagerView && $viewedUserId && auth()->id() !== $viewedUserId)
        ? ['assigned_user_id' => $viewedUserId, 'mine' => 0]
        : ['mine' => 1];
    $myQueueParams = ($isManagerView && $viewedUserId && auth()->id() !== $viewedUserId)
        ? ['first_caller_user_id' => $viewedUserId, 'status' => 'new', 'mine' => 0]
        : [];
    $perfFrom = optional($performancePeriod['from'] ?? null)->format('Y-m-d');
    $perfTo = optional($performancePeriod['to'] ?? null)->format('Y-m-d');
    $perfView = $performanceView ?? 'month';
    $perfPreset = $performancePreset ?? '';
    $perfAnchor = ($performanceAnchorDate ?? now())->copy();
    $perfAnchorDate = $perfAnchor->format('Y-m-d');
    $perfIsToday = $perfAnchor->isSameDay(now());
    $perfPrevDate = match ($perfView) {
        'day' => $perfAnchor->copy()->subDay()->format('Y-m-d'),
        'week' => $perfAnchor->copy()->subWeek()->format('Y-m-d'),
        default => $perfAnchor->copy()->subMonthNoOverflow()->format('Y-m-d'),
    };
    $perfNextDate = match ($perfView) {
        'day' => $perfAnchor->copy()->addDay()->format('Y-m-d'),
        'week' => $perfAnchor->copy()->addWeek()->format('Y-m-d'),
        default => $perfAnchor->copy()->addMonthNoOverflow()->format('Y-m-d'),
    };
    $perfCenterLabel = match ($perfView) {
        'day' => $perfAnchor->format('d.m.Y'),
        'week' => 'Tyden '.$perfAnchor->isoFormat('W/YYYY'),
        default => $perfAnchor->locale('cs')->isoFormat('MMMM YYYY'),
    };
    $perfBaseParams = $isManagerView && $viewedUserId ? ['user_view_id' => $viewedUserId] : [];
    $monthlyTarget = $monthlyTargetProgress ?? null;
@endphp

@extends('layouts.crm', ['title' => 'Prehled | Call CRM'])

@section('content')
    @if (auth()->user()?->isManager())
        <div class="mb-4 w-full rounded-xl px-3 py-2 shadow-sm ring-1 {{ $isViewingOtherUser ? 'bg-amber-50 ring-amber-200' : 'bg-white ring-slate-200' }}">
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2 xl:flex-nowrap">
                <div class="min-w-56 flex-1">
                    <label for="user_view_id" class="sr-only">Pohled uzivatele</label>
                    <select id="user_view_id" name="user_view_id" class="mt-1 h-9 w-full rounded-md border-slate-300 text-sm text-slate-700 {{ $isViewingOtherUser ? 'bg-amber-100/60 border-amber-300' : '' }}">
                        @foreach ($dashboardUsers as $userOption)
                            <option value="{{ $userOption->id }}" @selected($viewedUserId === $userOption->id)>Pohled: {{ $userOption->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="h-9 rounded-md bg-slate-900 px-3 text-sm font-medium text-white">Prepnout</button>
                @if ($isViewingOtherUser)
                    <a href="{{ route('dashboard') }}" class="h-9 rounded-md bg-amber-100 px-3 text-sm font-medium text-amber-900 ring-1 ring-amber-300 inline-flex items-center">Zpet na muj pohled</a>
                    <div class="text-xs text-amber-900 xl:ml-auto whitespace-nowrap">Pohled: <span class="font-semibold">{{ $viewedUser?->name }}</span></div>
                @endif
            </form>
        </div>
    @endif

    <div class="mb-8 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">Vyhodnoceni obvolani NEW firem</h2>
            </div>
        </div>

        <div class="mb-3 flex flex-wrap items-center gap-2">
            <div class="inline-flex h-10 items-center rounded-lg bg-slate-100 p-1 ring-1 ring-slate-200">
                @foreach (['day' => 'Den', 'week' => 'Tyden', 'month' => 'Mesic'] as $modeValue => $modeLabel)
                    <a href="{{ route('dashboard', array_merge($perfBaseParams, ['perf_view' => $modeValue, 'perf_date' => $perfAnchorDate])) }}"
                       class="inline-flex h-8 items-center rounded-md px-3 text-sm font-medium transition {{ $perfView === $modeValue ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:text-slate-900' }}">
                        {{ $modeLabel }}
                    </a>
                @endforeach
            </div>

            <div class="inline-flex h-10 items-center gap-2 rounded-lg bg-white px-2 py-1 ring-1 ring-slate-200">
                <a href="{{ route('dashboard', array_merge($perfBaseParams, ['perf_view' => $perfView, 'perf_date' => $perfPrevDate])) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-200 text-slate-700" title="Predchozi" aria-label="Predchozi">
                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12.5 4.5 7 10l5.5 5.5" />
                    </svg>
                </a>
                <a href="{{ route('dashboard', array_merge($perfBaseParams, ['perf_view' => $perfView, 'perf_date' => now()->format('Y-m-d')])) }}" class="inline-flex h-8 w-44 items-center justify-center rounded-md bg-slate-900 px-3 text-sm font-medium text-white truncate" title="Skok na aktualni obdobi">
                    {{ $perfCenterLabel }}
                </a>
                <a href="{{ route('dashboard', array_merge($perfBaseParams, ['perf_view' => $perfView, 'perf_date' => $perfNextDate])) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-200 text-slate-700" title="Dalsi" aria-label="Dalsi">
                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7.5 4.5 13 10l-5.5 5.5" />
                    </svg>
                </a>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="inline-flex flex-wrap items-end gap-2 rounded-lg bg-white px-2 py-1 ring-1 ring-slate-200">
                @if ($isManagerView && $viewedUserId)
                    <input type="hidden" name="user_view_id" value="{{ $viewedUserId }}">
                @endif
                <input type="hidden" name="perf_view" value="{{ $perfView }}">
                <input type="hidden" name="perf_date" value="{{ $perfAnchorDate }}">
                <div>
                    <label for="perf_from" class="sr-only">Od</label>
                    <input id="perf_from" name="perf_from" type="date" value="{{ $perfFrom }}" class="h-8 rounded-md border-slate-300 text-sm">
                </div>
                <div>
                    <label for="perf_to" class="sr-only">Do</label>
                    <input id="perf_to" name="perf_to" type="date" value="{{ $perfTo }}" class="h-8 rounded-md border-slate-300 text-sm">
                </div>
                <button type="submit" class="h-8 rounded-md bg-slate-900 px-3 text-sm font-medium text-white">Zobrazit</button>
            </form>
        </div>

        @if ($viewedUserPerformance)
            <div class="mb-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-3">
                    <div class="text-xs text-slate-500">Obvolane NEW firmy</div>
                    <div class="mt-1 text-2xl font-semibold">{{ $viewedUserPerformance->new_called_companies }}</div>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-3">
                    <div class="text-xs text-emerald-700">Z toho schuzky</div>
                    <div class="mt-1 text-2xl font-semibold text-emerald-800">{{ $viewedUserPerformance->meeting_companies }}</div>
                    <div class="text-xs text-emerald-700">{{ $viewedUserPerformance->meeting_rate }} %</div>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-3">
                    <div class="text-xs text-blue-700">Z toho dealy</div>
                    <div class="mt-1 text-2xl font-semibold text-blue-800">{{ $viewedUserPerformance->deal_companies }}</div>
                    <div class="text-xs text-blue-700">{{ $viewedUserPerformance->deal_rate }} %</div>
                </div>
            </div>
        @endif

        @if ($isManagerView && ! $isViewingOtherUser)
            <div class="overflow-hidden rounded-xl ring-1 ring-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-2">Uzivatel</th>
                            <th class="px-4 py-2">Obvolane NEW</th>
                            <th class="px-4 py-2">Schuzky</th>
                            <th class="px-4 py-2">Schuzky %</th>
                            <th class="px-4 py-2">Dealy</th>
                            <th class="px-4 py-2">Dealy %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($userPerformance as $row)
                            <tr class="{{ $viewedUserId === $row->id ? 'bg-amber-50/40' : '' }}">
                                <td class="px-4 py-2">
                                    <a href="{{ route('dashboard', array_merge(request()->except('user_view_id', 'page'), ['user_view_id' => $row->id])) }}" class="text-slate-800 underline">
                                        {{ $row->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 font-medium">{{ $row->new_called_companies }}</td>
                                <td class="px-4 py-2">{{ $row->meeting_companies }}</td>
                                <td class="px-4 py-2">{{ $row->meeting_rate }} %</td>
                                <td class="px-4 py-2">{{ $row->deal_companies }}</td>
                                <td class="px-4 py-2">{{ $row->deal_rate }} %</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-8">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold">{{ $isViewingOtherUser ? 'Fronta uzivatele: '.$viewedUser?->name : 'Moje fronta' }}</h2>
            <div class="mt-4 grid gap-3 lg:grid-cols-3">
                <a href="{{ $isViewingOtherUser ? route('companies.index', array_merge($myQueueParams, ['mine' => 0])) : route('companies.queue.mine') }}" class="rounded-lg border border-emerald-200 bg-emerald-50/30 p-3 transition hover:bg-emerald-50/50">
                    <div class="text-xs text-emerald-700">{{ $isViewingOtherUser ? 'Fronta uzivatele (new)' : 'Moje fronta k obvolani' }}</div>
                    <div class="mt-1 text-xl font-semibold text-emerald-800">{{ $myStats['queueCompanies'] ?? 0 }}</div>
                </a>
                <div class="rounded-lg border border-blue-200 bg-blue-50/30 p-3">
                    <div class="text-xs text-blue-700">Cil obvolani (mesic)</div>
                    <div class="mt-1 text-xl font-semibold text-blue-800">
                        @if (!empty($monthlyTarget['target']))
                            {{ $monthlyTarget['called'] }} / {{ $monthlyTarget['target'] }}
                        @else
                            {{ $monthlyTarget['called'] ?? 0 }}
                        @endif
                    </div>
                    <div class="mt-1 text-[11px] text-blue-700">
                        @if (!empty($monthlyTarget['target']) && !is_null($monthlyTarget['remaining']))
                            zbyva {{ $monthlyTarget['remaining'] }}
                        @else
                            nastavte cil v administraci
                        @endif
                    </div>
                </div>
                <a href="{{ route('companies.index', $myCompaniesParams) }}" class="rounded-lg border border-slate-200 p-3 transition hover:bg-slate-50">
                    <div class="text-xs text-slate-500">{{ $isViewingOtherUser ? 'Firmy uzivatele' : 'Moje firmy' }}</div>
                    <div class="mt-1 text-xl font-semibold">{{ $myStats['ownerCompanies'] ?? 0 }}</div>
                    @if ($targetCount || $targetUntil)
                        <div class="mt-1 text-[11px] text-slate-500">
                            {{ $targetCount ? 'cil '.$targetCount : 'cil ?' }}@if($targetUntil) · do {{ $targetUntil->format('Y-m-d') }}@endif
                        </div>
                    @endif
                </a>
            </div>

            <div class="mt-4">
                @if ($dashboardQueueOverdue->isNotEmpty())
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-rose-700">Overdue</div>
                    @foreach ($dashboardQueueOverdue as $item)
                        @php
                            $type = $item['type'];
                            $isMeeting = $type === 'meeting';
                            $isFollowUp = $type === 'follow-up';
                            $isCompany = $type === 'company';
                            $lineClass = $isMeeting ? 'bg-emerald-500' : ($isFollowUp ? 'bg-amber-500' : 'bg-white');
                            $typeBadgeClass = $isMeeting
                                ? 'bg-emerald-50 text-emerald-800 ring-emerald-200'
                                : ($isFollowUp ? 'bg-amber-50 text-amber-800 ring-amber-200' : 'bg-slate-100 text-slate-700 ring-slate-200');
                            $timeText = $item['at']?->format('Y-m-d H:i') ?? '-';
                            $detailUrl = $isCompany
                                ? route('companies.show', $item['company'])
                                : ($isFollowUp
                                    ? route('follow-ups.show', $item['followUp'])
                                    : route('meetings.show', $item['meeting']));
                        @endphp
                        <div class="relative mb-2 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 cursor-pointer hover:brightness-[0.99]" data-row-link="{{ $detailUrl }}">
                            <div class="absolute inset-y-0 left-0 w-1 {{ $lineClass }}"></div>
                            <div class="pl-4 pr-4 py-3">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $typeBadgeClass }}">
                                                {{ $isMeeting ? 'Schuzka' : ($isFollowUp ? 'Follow-up' : 'NEW') }}
                                            </span>
                                            <span class="text-xs text-slate-500">{{ $timeText }}</span>
                                        </div>
                                        <div class="mt-1 text-base font-semibold text-slate-900">
                                            {{ $isCompany ? ($item['company']->name ?? '-') : ($isFollowUp ? ($item['followUp']->company?->name ?? '-') : ($item['meeting']->company?->name ?? '-')) }}
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2" data-row-link-ignore>
                                        @if ($isCompany)
                                            <form method="POST" action="{{ route('companies.calls.start', $item['company']) }}" class="inline-flex" data-row-link-ignore>
                                                @csrf
                                                <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-600 text-white" title="Zahajit hovor" aria-label="Zahajit hovor">
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            <a href="{{ route('companies.show', $item['company']) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Detail</a>
                                        @elseif ($isFollowUp)
                                            @if ($item['followUp']->company)
                                                <form method="POST" action="{{ route('companies.calls.start', $item['followUp']->company) }}" class="inline-flex" data-row-link-ignore>
                                                    @csrf
                                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-600 text-white" title="Zahajit hovor" aria-label="Zahajit hovor">
                                                        <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('follow-ups.quick-status', $item['followUp']) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="done">
                                                <input type="hidden" name="company_status" value="follow-up">
                                                <button type="submit" class="rounded-md bg-amber-600 px-3 py-2 text-xs font-medium text-white">Hotovo</button>
                                            </form>
                                            <a href="{{ route('follow-ups.edit', $item['followUp']) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Preplanovat</a>
                                        @else
                                            <a href="{{ route('meetings.show', $item['meeting']) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Detail schuzky</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                @if ($dashboardQueueToday->isNotEmpty())
                    <div class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Dnes</div>
                    @foreach ($dashboardQueueToday as $item)
                        @php
                            $type = $item['type'];
                            $isMeeting = $type === 'meeting';
                            $isFollowUp = $type === 'follow-up';
                            $isCompany = $type === 'company';
                            $lineClass = $isMeeting ? 'bg-emerald-500' : ($isFollowUp ? 'bg-amber-500' : 'bg-white');
                            $typeBadgeClass = $isMeeting
                                ? 'bg-emerald-50 text-emerald-800 ring-emerald-200'
                                : ($isFollowUp ? 'bg-amber-50 text-amber-800 ring-amber-200' : 'bg-slate-100 text-slate-700 ring-slate-200');
                            $timeText = $item['at']?->format('Y-m-d H:i') ?? '-';
                            $detailUrl = $isCompany
                                ? route('companies.show', $item['company'])
                                : ($isFollowUp
                                    ? route('follow-ups.show', $item['followUp'])
                                    : route('meetings.show', $item['meeting']));
                        @endphp
                        <div class="relative mb-2 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 cursor-pointer hover:brightness-[0.99]" data-row-link="{{ $detailUrl }}">
                            <div class="absolute inset-y-0 left-0 w-1 {{ $lineClass }}"></div>
                            <div class="pl-4 pr-4 py-3">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $typeBadgeClass }}">
                                                {{ $isMeeting ? 'Schuzka' : ($isFollowUp ? 'Follow-up' : 'NEW') }}
                                            </span>
                                            <span class="text-xs text-slate-500">{{ $timeText }}</span>
                                        </div>
                                        <div class="mt-1 text-base font-semibold text-slate-900">
                                            {{ $isCompany ? ($item['company']->name ?? '-') : ($isFollowUp ? ($item['followUp']->company?->name ?? '-') : ($item['meeting']->company?->name ?? '-')) }}
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2" data-row-link-ignore>
                                        @if ($isCompany)
                                            <form method="POST" action="{{ route('companies.calls.start', $item['company']) }}" class="inline-flex" data-row-link-ignore>
                                                @csrf
                                                <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-600 text-white" title="Zahajit hovor" aria-label="Zahajit hovor">
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            <a href="{{ route('companies.show', $item['company']) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Detail</a>
                                        @elseif ($isFollowUp)
                                            @if ($item['followUp']->company)
                                                <form method="POST" action="{{ route('companies.calls.start', $item['followUp']->company) }}" class="inline-flex" data-row-link-ignore>
                                                    @csrf
                                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-600 text-white" title="Zahajit hovor" aria-label="Zahajit hovor">
                                                        <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('follow-ups.quick-status', $item['followUp']) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="done">
                                                <input type="hidden" name="company_status" value="follow-up">
                                                <button type="submit" class="rounded-md bg-amber-600 px-3 py-2 text-xs font-medium text-white">Hotovo</button>
                                            </form>
                                            <a href="{{ route('follow-ups.edit', $item['followUp']) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Preplanovat</a>
                                        @else
                                            <a href="{{ route('meetings.show', $item['meeting']) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Detail schuzky</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                @if ($dashboardQueueFuture->isNotEmpty())
                    <div class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Budouci</div>
                    @foreach ($dashboardQueueFuture as $item)
                        @php
                            $type = $item['type'];
                            $isMeeting = $type === 'meeting';
                            $isFollowUp = $type === 'follow-up';
                            $isCompany = $type === 'company';
                            $lineClass = $isMeeting ? 'bg-emerald-500' : ($isFollowUp ? 'bg-amber-500' : 'bg-white');
                            $typeBadgeClass = $isMeeting
                                ? 'bg-emerald-50 text-emerald-800 ring-emerald-200'
                                : ($isFollowUp ? 'bg-amber-50 text-amber-800 ring-amber-200' : 'bg-slate-100 text-slate-700 ring-slate-200');
                            $timeText = $item['at']?->format('Y-m-d H:i') ?? '-';
                            $detailUrl = $isCompany
                                ? route('companies.show', $item['company'])
                                : ($isFollowUp
                                    ? route('follow-ups.show', $item['followUp'])
                                    : route('meetings.show', $item['meeting']));
                        @endphp
                        <div class="relative mb-2 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 cursor-pointer hover:brightness-[0.99]" data-row-link="{{ $detailUrl }}">
                            <div class="absolute inset-y-0 left-0 w-1 {{ $lineClass }}"></div>
                            <div class="pl-4 pr-4 py-3">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $typeBadgeClass }}">
                                                {{ $isMeeting ? 'Schuzka' : ($isFollowUp ? 'Follow-up' : 'NEW') }}
                                            </span>
                                            <span class="text-xs text-slate-500">{{ $timeText }}</span>
                                        </div>
                                        <div class="mt-1 text-base font-semibold text-slate-900">
                                            {{ $isCompany ? ($item['company']->name ?? '-') : ($isFollowUp ? ($item['followUp']->company?->name ?? '-') : ($item['meeting']->company?->name ?? '-')) }}
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2" data-row-link-ignore>
                                        @if ($isCompany)
                                            <form method="POST" action="{{ route('companies.calls.start', $item['company']) }}" class="inline-flex" data-row-link-ignore>
                                                @csrf
                                                <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-600 text-white" title="Zahajit hovor" aria-label="Zahajit hovor">
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            <a href="{{ route('companies.show', $item['company']) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Detail</a>
                                        @elseif ($isFollowUp)
                                            @if ($item['followUp']->company)
                                                <form method="POST" action="{{ route('companies.calls.start', $item['followUp']->company) }}" class="inline-flex" data-row-link-ignore>
                                                    @csrf
                                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-600 text-white" title="Zahajit hovor" aria-label="Zahajit hovor">
                                                        <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('follow-ups.quick-status', $item['followUp']) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="done">
                                                <input type="hidden" name="company_status" value="follow-up">
                                                <button type="submit" class="rounded-md bg-amber-600 px-3 py-2 text-xs font-medium text-white">Hotovo</button>
                                            </form>
                                            <a href="{{ route('follow-ups.edit', $item['followUp']) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Preplanovat</a>
                                        @else
                                            <a href="{{ route('meetings.show', $item['meeting']) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Detail schuzky</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                @if ($dashboardQueueOverdue->isEmpty() && $dashboardQueueToday->isEmpty() && $dashboardQueueFuture->isEmpty())
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">
                        {{ $isViewingOtherUser ? 'Vybrany uzivatel nema polozky ve fronte.' : 'Nemate zadne polozky ve fronte.' }}
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
