@extends('layouts.crm', ['title' => 'Moje fronta | Call CRM'])

@section('content')
    @php
        $viewer = auth()->user();
        $viewerId = (int) ($viewer?->id ?? 0);
        $viewerIsManager = $viewer?->isManager() ?? false;
        $queueFeed = collect();

        foreach ($companies as $index => $company) {
            $queueFeed->push([
                'type' => 'company',
                'at' => $company->first_caller_assigned_at ?? $company->created_at,
                'sort_ts' => optional($company->first_caller_assigned_at ?? $company->created_at)?->getTimestamp() ?? 0,
                'company' => $company,
            ]);
        }

        foreach ($followUps as $followUp) {
            $queueFeed->push([
                'type' => 'follow-up',
                'at' => $followUp->due_at,
                'sort_ts' => optional($followUp->due_at)?->getTimestamp() ?? 0,
                'followUp' => $followUp,
                'company' => $followUp->company,
            ]);
        }

        foreach ($meetings as $meeting) {
            $queueFeed->push([
                'type' => 'meeting',
                'at' => $meeting->scheduled_at,
                'sort_ts' => optional($meeting->scheduled_at)?->getTimestamp() ?? 0,
                'meeting' => $meeting,
                'company' => $meeting->company,
            ]);
        }

        $queueFeed = $queueFeed
            ->sortBy([
                ['sort_ts', 'asc'],
                ['type', 'asc'],
            ])
            ->values();

        $startOfToday = now()->startOfDay();
        $endOfToday = now()->endOfDay();
        $queueOverdue = $queueFeed
            ->filter(fn ($item) => ($item['type'] ?? '') !== 'company')
            ->filter(fn ($item) => $item['at'] && $item['at']->lt($startOfToday))
            ->values();
        $queueToday = $queueFeed
            ->filter(fn ($item) => ($item['type'] ?? '') === 'company'
                || ! $item['at']
                || ($item['at']->gte($startOfToday) && $item['at']->lte($endOfToday)))
            ->values();
        $queueFuture = $queueFeed
            ->filter(fn ($item) => ($item['type'] ?? '') !== 'company')
            ->filter(fn ($item) => $item['at'] && $item['at']->gt($endOfToday))
            ->values();
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200">
        <div class="min-w-0">
            Ve fronte je <span class="font-semibold">{{ $companies->total() }}</span> firem.
            New firmy + otevrene follow-upy + schuzky serazene podle casu.
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('companies.next-mine', ['skip_lost' => 1]) }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Moje dalsi firma</a>
            <a href="{{ route('companies.index', ['mine' => 1]) }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700">Vsechny moje firmy</a>
        </div>
    </div>

    <div>
        @php $renderQueueItem = function ($item) use ($viewerId, $viewerIsManager) { @endphp
            @php
                $type = $item['type'];
                $isMeeting = $type === 'meeting';
                $isFollowUp = $type === 'follow-up';
                $isCompany = $type === 'company';
                $lineClass = $isMeeting ? 'bg-emerald-500' : ($isFollowUp ? 'bg-amber-500' : 'bg-white');
                $typeBadgeClass = $isMeeting
                    ? 'bg-emerald-50 text-emerald-800 ring-emerald-200'
                    : ($isFollowUp ? 'bg-amber-50 text-amber-800 ring-amber-200' : 'bg-slate-100 text-slate-700 ring-slate-200');
                $containerPadding = $isMeeting ? 'py-7' : 'py-3';
                $timeText = $item['at']?->format('Y-m-d H:i') ?? '-';
            @endphp

            @if ($isCompany)
                @php $company = $item['company']; @endphp
                @php
                    $canStartCall = $viewerIsManager
                        || $company->first_contacted_at !== null
                        || $company->first_caller_user_id === null
                        || (int) $company->first_caller_user_id === $viewerId;
                @endphp
                <div class="relative mb-3 cursor-pointer overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 hover:brightness-[0.99]" data-row-link="{{ route('companies.show', $company) }}">
                    <div class="absolute inset-y-0 left-0 w-1 {{ $lineClass }}"></div>
                    <div class="pl-4 pr-4 {{ $containerPadding }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $typeBadgeClass }}">NEW</span>
                                    <span class="text-xs text-slate-500">{{ $timeText }}</span>
                                </div>
                                <div class="mt-1 text-base font-semibold text-slate-900">{{ $company->name }}</div>
                                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                    <span>ICO: {{ $company->ico ?: '-' }}</span>
                                    <span>Owner: {{ $company->assignedUser?->name ?? '-' }}</span>
                                    <span>Queue: {{ $company->first_caller_assigned_at?->format('Y-m-d H:i') ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2" data-row-link-ignore>
                                @if ($canStartCall)
                                    <form method="POST" action="{{ route('companies.calls.start', $company) }}" class="inline-flex" data-row-link-ignore>
                                        @csrf
                                        <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-600 text-white" title="Zahajit hovor" aria-label="Zahajit hovor">
                                            <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-slate-200 text-slate-500 opacity-70 cursor-not-allowed" title="Firma je v queue prirazena jinemu callerovi." aria-label="Hovor neni dostupny">
                                        <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/>
                                        </svg>
                                    </span>
                                @endif
                                <form method="POST" action="{{ route('companies.quick-defer', $company) }}" class="inline-flex" data-row-link-ignore>
                                    @csrf
                                    <button type="submit" class="rounded-md bg-amber-100 px-3 py-2 text-xs font-medium text-amber-800 ring-1 ring-amber-200">Odlozit</button>
                                </form>
                                <a href="{{ route('companies.show', $company) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($isFollowUp)
                @php $followUp = $item['followUp']; @endphp
                @php
                    $followUpCompany = $followUp->company;
                    $canStartCall = $followUpCompany
                        && (
                            $viewerIsManager
                            || $followUpCompany->first_contacted_at !== null
                            || $followUpCompany->first_caller_user_id === null
                            || (int) $followUpCompany->first_caller_user_id === $viewerId
                        );
                @endphp
                <div class="relative mb-3 cursor-pointer overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 hover:brightness-[0.99]" data-row-link="{{ route('follow-ups.show', $followUp) }}">
                    <div class="absolute inset-y-0 left-0 w-1 {{ $lineClass }}"></div>
                    <div class="pl-4 pr-4 {{ $containerPadding }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $typeBadgeClass }}">Follow-up</span>
                                    <span class="text-xs text-slate-500">{{ $timeText }}</span>
                                </div>
                                <div class="mt-1 text-base font-semibold text-slate-900">{{ $followUp->company?->name ?? '-' }}</div>
                                @if ($followUp->note)
                                    <div class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $followUp->note }}</div>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2" data-row-link-ignore>
                                @if ($followUpCompany && $canStartCall)
                                    <form method="POST" action="{{ route('companies.calls.start', $followUpCompany) }}" class="inline-flex" data-row-link-ignore>
                                        @csrf
                                        <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-600 text-white" title="Zahajit hovor" aria-label="Zahajit hovor">
                                            <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @elseif ($followUpCompany)
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-slate-200 text-slate-500 opacity-70 cursor-not-allowed" title="Firma je v queue prirazena jinemu callerovi." aria-label="Hovor neni dostupny">
                                        <svg viewBox="0 0 20 20" fill="none" class="h-3.5 w-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/>
                                        </svg>
                                    </span>
                                @endif
                                <form method="POST" action="{{ route('follow-ups.quick-status', $followUp) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="done">
                                    <input type="hidden" name="company_status" value="follow-up">
                                    <button type="submit" class="rounded-md bg-amber-600 px-3 py-2 text-xs font-medium text-white">Hotovo (kontaktovano)</button>
                                </form>
                                <a href="{{ route('follow-ups.edit', $followUp) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Preplanovat</a>
                                <a href="{{ route('follow-ups.show', $followUp) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                @php $meeting = $item['meeting']; @endphp
                <div class="relative my-5 cursor-pointer overflow-hidden rounded-xl bg-emerald-50/40 shadow-sm ring-1 ring-emerald-200 hover:brightness-[0.99]" data-row-link="{{ route('meetings.show', $meeting) }}">
                    <div class="absolute inset-y-0 left-0 w-1 {{ $lineClass }}"></div>
                    <div class="pl-4 pr-4 {{ $containerPadding }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $typeBadgeClass }}">Schuzka</span>
                                    <span class="text-xs text-slate-500">{{ $timeText }}</span>
                                    @if ($meeting->status)
                                        <span class="text-xs text-slate-500">{{ $meeting->status }}</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">{{ $meeting->company?->name ?? '-' }}</div>
                                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                    @if ($meeting->mode)
                                        <span>Typ: {{ $meeting->mode }}</span>
                                    @endif
                                    @if ($meeting->scheduled_at)
                                        <span>Zacatek: {{ $meeting->scheduled_at->format('Y-m-d H:i') }}</span>
                                    @endif
                                </div>
                                @if ($meeting->note)
                                    <div class="mt-2 line-clamp-3 whitespace-pre-line text-sm text-slate-600">{{ $meeting->note }}</div>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2" data-row-link-ignore>
                                <a href="{{ route('meetings.edit', $meeting) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Upravit</a>
                                <a href="{{ route('meetings.show', $meeting) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @php }; @endphp

        @if ($queueOverdue->isNotEmpty())
            <div class="mb-4 rounded-xl border border-rose-400 bg-rose-100/70 p-3 ring-1 ring-rose-200">
                <div class="mb-2 flex items-center gap-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-rose-700">Overdue</div>
                    <div class="h-px flex-1 bg-rose-300"></div>
                    <button type="button"
                        class="rounded-md bg-white px-2 py-1 text-xs font-medium text-rose-800 ring-1 ring-rose-300"
                        data-overdue-toggle>
                        Schovat
                    </button>
                </div>
                <div data-overdue-list>
                    @foreach ($queueOverdue as $item)
                        @php $renderQueueItem($item); @endphp
                    @endforeach
                </div>
            </div>
        @endif

        @if ($queueToday->isNotEmpty())
            <div class="mb-2 mt-4 flex items-center gap-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dnes</div>
                <div class="h-px flex-1 bg-slate-200"></div>
            </div>
            @foreach ($queueToday as $item)
                @php $renderQueueItem($item); @endphp
            @endforeach
        @endif

        @if ($queueFuture->isNotEmpty())
            <div class="mb-2 mt-6 flex items-center gap-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Budouci</div>
                <div class="h-px flex-1 bg-slate-200"></div>
            </div>
            @foreach ($queueFuture as $item)
                @php $renderQueueItem($item); @endphp
            @endforeach
        @endif

        @if ($queueOverdue->isEmpty() && $queueToday->isEmpty() && $queueFuture->isEmpty())
            <div class="rounded-xl bg-white p-8 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">
                Vase fronta je prazdna.
            </div>
        @endif
    </div>

    @if ($queueOverdue->isNotEmpty())
        <script>
            (function () {
                const toggleBtn = document.querySelector('[data-overdue-toggle]');
                const overdueList = document.querySelector('[data-overdue-list]');
                if (!toggleBtn || !overdueList) return;

                const storageKey = 'crm-queue-overdue-collapsed';
                const applyState = (collapsed) => {
                    overdueList.classList.toggle('hidden', collapsed);
                    toggleBtn.textContent = collapsed ? 'Zobrazit' : 'Schovat';
                };

                let collapsed = false;
                try {
                    collapsed = localStorage.getItem(storageKey) === '1';
                } catch (e) {}

                applyState(collapsed);

                toggleBtn.addEventListener('click', () => {
                    collapsed = !collapsed;
                    applyState(collapsed);
                    try {
                        localStorage.setItem(storageKey, collapsed ? '1' : '0');
                    } catch (e) {}
                });
            })();
        </script>
    @endif

    <div class="mt-4">
        {{ $companies->links() }}
    </div>
@endsection
