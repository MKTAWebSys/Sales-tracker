@extends('layouts.crm', ['title' => 'Kalendar | Call CRM'])

@section('content')
    @php
        $todayDate = now()->format('Y-m-d');
        $isToday = $calendarDate->isSameDay(now());
        $viewMode = $viewMode ?? 'week';
        $today = now()->startOfDay();

        if ($viewMode === 'day') {
            $prevDate = $calendarDate->copy()->subDay()->format('Y-m-d');
            $nextDate = $calendarDate->copy()->addDay()->format('Y-m-d');
        } elseif ($viewMode === 'month') {
            $prevDate = $calendarDate->copy()->subMonthNoOverflow()->format('Y-m-d');
            $nextDate = $calendarDate->copy()->addMonthNoOverflow()->format('Y-m-d');
        } else {
            $prevDate = $calendarDate->copy()->subWeek()->format('Y-m-d');
            $nextDate = $calendarDate->copy()->addWeek()->format('Y-m-d');
        }

        $dayHeaders = ['Po', 'Ut', 'St', 'Ct', 'Pa', 'So', 'Ne'];
    @endphp

    <form id="calendar-filter-form" method="GET" action="{{ route('calendar.index') }}" class="mb-4">
    <input type="hidden" name="date" value="{{ $calendarDate->format('Y-m-d') }}">
    <input type="hidden" name="view" value="{{ $viewMode }}">

    <div class="grid gap-3 xl:grid-cols-[18rem_18rem_minmax(24rem,1fr)_5.75rem] xl:items-stretch">
        <div class="rounded-xl bg-white p-3 ring-1 ring-slate-200 h-[5.75rem] flex items-center">
            <div class="inline-flex rounded-lg bg-slate-100 p-1 ring-1 ring-slate-200">
                @foreach (['day' => 'Den', 'week' => 'Tyden', 'month' => 'Mesic'] as $modeValue => $modeLabel)
                    <a href="{{ route('calendar.index', array_merge(request()->except('page'), ['view' => $modeValue, 'date' => $calendarDate->format('Y-m-d')])) }}"
                       class="rounded-md px-3 py-1.5 text-sm font-medium transition {{ $viewMode === $modeValue ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:text-slate-900' }}">
                        {{ $modeLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl bg-white p-3 ring-1 ring-slate-200 h-[5.75rem]">
            <div class="flex h-full flex-wrap items-center gap-2 text-sm">
                <a href="{{ route('calendar.index', array_merge(request()->except('date', 'page'), ['date' => $prevDate])) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-slate-200 text-slate-700" title="Predchozi" aria-label="Predchozi">
                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12.5 4.5 7 10l5.5 5.5" />
                    </svg>
                </a>
                <a href="{{ route('calendar.index', array_merge(request()->except('date', 'page'), ['date' => $todayDate])) }}" class="rounded-md {{ $isToday ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-700' }} px-3 py-1.5 font-medium">Dnes</a>
                <a href="{{ route('calendar.index', array_merge(request()->except('date', 'page'), ['date' => $nextDate])) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-slate-200 text-slate-700" title="Dalsi" aria-label="Dalsi">
                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7.5 4.5 13 10l-5.5 5.5" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="rounded-xl bg-white p-3 ring-1 ring-slate-200 h-[5.75rem]">
                <div class="grid h-full gap-3 {{ $isManager ? 'grid-cols-[minmax(0,13rem)_minmax(0,1fr)]' : 'grid-cols-1' }}">
                    @if ($isManager)
                        <div class="self-center">
                            <select id="calendar_header_assigned_user_id" name="assigned_user_id" form="calendar-filter-form" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" class="w-full rounded-md border-slate-300 py-1.5 text-sm">
                                <option value="">Uzivatel: Vse</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(($filters['assigned_user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="space-y-1.5 self-center">
                    @if ($isManager)
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 whitespace-nowrap">
                            <input type="hidden" name="mine" value="0">
                            <input type="checkbox" name="mine" value="1" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" class="rounded border-slate-300" @checked(($filters['mine'] ?? '1') === '1')>
                            <span>Jen moje agenda</span>
                        </label>
                    @else
                        <input type="hidden" name="mine" value="1">
                        <div class="text-xs text-slate-500 whitespace-nowrap">Jen vase agenda</div>
                    @endif
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 whitespace-nowrap">
                        <input type="checkbox" name="overdue_only" value="1" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" class="rounded border-slate-300" @checked(!empty($filters['overdue_only']))>
                        <span>Jen overdue</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-3 ring-1 ring-slate-200 h-[5.75rem]">
            <div class="flex h-full items-center justify-center">
                <a href="{{ route('calendar.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-white text-slate-600 ring-1 ring-slate-300 hover:text-slate-900" title="Reset" aria-label="Reset">
                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 10a6 6 0 1 1-2-4.47" />
                        <path d="M16 4v4h-4" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</form>

    <div class="mb-3 grid gap-2 sm:grid-cols-3">
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
            <div class="text-xs text-slate-500">Vybrany den (agenda dole)</div>
            <div class="mt-0.5 flex flex-wrap items-baseline gap-2">
                <div class="text-xl font-semibold">{{ $counts['total'] }}</div>
                <div class="text-xs text-slate-500">{{ $calendarDate->format('Y-m-d') }}</div>
            </div>
        </div>
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-blue-200">
            <div class="text-xs text-blue-700">Aktivity v zobrazenem obdobi</div>
            <div class="mt-0.5 flex flex-wrap items-baseline gap-2">
                <div class="text-xl font-semibold text-blue-800">{{ $rangeCounts['total'] ?? $counts['total'] }}</div>
                <div class="text-xs text-slate-500">
                    follow-upy + schuzky
                @if (!empty($rangeCounts['doneTotal']))
                    | hotovo {{ $rangeCounts['doneTotal'] }}
                @endif
                </div>
            </div>
        </div>
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
            <div class="text-xs text-slate-500">Dnes (vybrany den)</div>
            <div class="mt-0.5 flex gap-4 text-sm">
                <span><span class="font-semibold text-amber-700">FU</span>: {{ $counts['followUps'] }}</span>
                <span><span class="font-semibold text-emerald-700">SCH</span>: {{ $counts['meetings'] }}</span>
            </div>
        </div>
    </div>

    <div class="mb-4 rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
        @if (($calendarGrid['type'] ?? '') === 'day')
            @php $cell = $calendarGrid['days'][0] ?? null; @endphp
            @if ($cell)
                @php
                    $isOverdueDay = $cell['date']->lt($today) && ($cell['counts']['todoTotal'] ?? 0) > 0;
                    $hasTodo = ($cell['counts']['todoTotal'] ?? 0) > 0;
                    $isOverloadedDay = ($cell['counts']['todoTotal'] ?? 0) >= 8;
                @endphp
                <div class="rounded-xl border p-4 {{ $isOverloadedDay ? 'border-fuchsia-300 bg-fuchsia-50/25 ring-1 ring-fuchsia-100' : ($isOverdueDay ? 'border-rose-200 bg-rose-50/30' : ($hasTodo ? 'border-blue-200 bg-blue-50/30' : 'border-slate-200 bg-slate-50/40')) }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold">{{ $cell['date']->format('l') }} {{ $cell['date']->format('Y-m-d') }}</div>
                            <div class="text-xs text-slate-500">
                                Denni souhrn aktivit
                                @if ($isOverloadedDay)
                                    | pretizeni (8+)
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('calendar.index', array_merge(request()->except('date'), ['date' => $cell['key'], 'view' => 'day'])) }}" class="text-xs text-slate-600 underline">Obnovit den</a>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                            <div class="text-xs text-slate-500">Neudelano</div>
                            <div class="mt-1 text-xl font-semibold">{{ $cell['counts']['todoTotal'] ?? 0 }}</div>
                        </div>
                        <div class="rounded-lg bg-amber-50 p-3 ring-1 ring-amber-200">
                            <div class="text-xs text-amber-700">Follow-upy</div>
                            <div class="mt-1 text-xl font-semibold text-amber-800">{{ $cell['counts']['followUps'] }}</div>
                        </div>
                        <div class="rounded-lg bg-emerald-50 p-3 ring-1 ring-emerald-200">
                            <div class="text-xs text-emerald-700">Hotovo</div>
                            <div class="mt-1 text-xl font-semibold text-emerald-800">{{ $cell['counts']['doneTotal'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            @endif
        @elseif (($calendarGrid['type'] ?? '') === 'week')
            <div class="grid gap-4 lg:grid-cols-[1fr_18rem]">
                <div class="grid gap-3 md:grid-cols-7">
                @foreach ($calendarGrid['days'] as $index => $cell)
                    @php
                        $isOverdueDay = $cell['date']->lt($today) && ($cell['counts']['todoTotal'] ?? 0) > 0;
                        $hasDone = ($cell['counts']['doneTotal'] ?? 0) > 0;
                        $isOverloadedDay = ($cell['counts']['todoTotal'] ?? 0) >= 8;
                        $baseClass = $cell['isSelected']
                            ? 'border-slate-900 bg-slate-900 text-white'
                            : ($isOverloadedDay
                                ? 'border-fuchsia-300 bg-fuchsia-50/35 hover:bg-fuchsia-50/55'
                                : ($isOverdueDay
                                ? 'border-rose-200 bg-rose-50/35 hover:bg-rose-50/55'
                                : (($cell['counts']['todoTotal'] ?? 0) > 0
                                    ? 'border-blue-200 bg-blue-50/30 hover:bg-blue-50/50'
                                    : ($hasDone
                                        ? 'border-emerald-200 bg-emerald-50/20 hover:bg-emerald-50/35'
                                        : 'border-slate-200 bg-slate-50/40 hover:bg-slate-50'))));
                    @endphp
                    <a href="{{ route('calendar.index', array_merge(request()->except('page'), ['date' => $cell['key'], 'view' => 'week'])) }}"
                       class="block rounded-xl border p-3 transition {{ $baseClass }}">
                        <div class="text-xs {{ $cell['isSelected'] ? 'text-slate-200' : 'text-slate-500' }}">{{ $dayHeaders[$index] }}</div>
                        <div class="mt-1 text-lg font-semibold">{{ $cell['date']->format('j.n.') }}</div>
                        <div class="mt-2 text-xs {{ $cell['isSelected'] ? 'text-slate-200' : 'text-slate-600' }}">Neudelano: {{ $cell['counts']['todoTotal'] ?? 0 }}</div>
                        <div class="mt-1 flex gap-2 text-[11px]">
                            <span class="{{ $cell['isSelected'] ? 'text-amber-200' : 'text-amber-700' }}">FU {{ $cell['counts']['todoFollowUps'] ?? 0 }}</span>
                            <span class="{{ $cell['isSelected'] ? 'text-emerald-200' : 'text-emerald-700' }}">SCH {{ $cell['counts']['todoMeetings'] ?? 0 }}</span>
                        </div>
                        <div class="mt-1 text-[11px] {{ $cell['isSelected'] ? 'text-slate-200' : 'text-slate-600' }}">
                            Hotovo: {{ $cell['counts']['doneTotal'] ?? 0 }}
                        </div>
                        @if ($isOverloadedDay)
                            <div class="mt-1 text-[10px] {{ $cell['isSelected'] ? 'text-fuchsia-200' : 'text-fuchsia-700' }}">Pretizeni 8+</div>
                        @endif
                    </a>
                @endforeach
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 lg:sticky lg:top-6 lg:self-start">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tydenni souhrn</div>
                    <div class="mt-1 text-sm text-slate-600">Pocet aktivit za den: hotovo vs neudelano.</div>
                    <div class="mt-4 space-y-2">
                        @foreach ($calendarGrid['days'] as $index => $cell)
                            @php
                                $isOverdueDay = $cell['date']->lt($today) && ($cell['counts']['todoTotal'] ?? 0) > 0;
                                $isOverloadedDay = ($cell['counts']['todoTotal'] ?? 0) >= 8;
                            @endphp
                            <a href="{{ route('calendar.index', array_merge(request()->except('page'), ['date' => $cell['key'], 'view' => 'week'])) }}"
                               class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm transition {{ $cell['isSelected'] ? 'border-slate-900 bg-slate-900 text-white' : ($isOverloadedDay ? 'border-fuchsia-300 bg-fuchsia-50/25 hover:bg-fuchsia-50/40' : ($isOverdueDay ? 'border-rose-200 bg-rose-50/30 hover:bg-rose-50/50' : 'border-slate-200 hover:bg-slate-50')) }}">
                                <div class="min-w-0">
                                    <div class="font-medium">{{ $dayHeaders[$index] }} {{ $cell['date']->format('j.n.') }}</div>
                                    <div class="text-xs {{ $cell['isSelected'] ? 'text-slate-200' : 'text-slate-500' }}">
                                        FU {{ $cell['counts']['todoFollowUps'] ?? 0 }} | SCH {{ $cell['counts']['todoMeetings'] ?? 0 }}
                                    </div>
                                </div>
                                <div class="text-right text-xs">
                                    <div class="{{ $cell['isSelected'] ? 'text-emerald-200' : 'text-emerald-700' }}">Hotovo {{ $cell['counts']['doneTotal'] ?? 0 }}</div>
                                    <div class="{{ $cell['isSelected'] ? 'text-amber-200' : 'text-amber-700' }}">Neudelano {{ $cell['counts']['todoTotal'] ?? 0 }}</div>
                                    @if ($isOverloadedDay)
                                        <div class="{{ $cell['isSelected'] ? 'text-fuchsia-200' : 'text-fuchsia-700' }}">Pretizeni</div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="grid grid-cols-7 gap-2 text-xs font-medium text-slate-500">
                @foreach ($dayHeaders as $label)
                    <div class="px-2 py-1">{{ $label }}</div>
                @endforeach
            </div>
            <div class="mt-2 grid gap-2">
                @foreach ($calendarGrid['rows'] as $row)
                    <div class="grid grid-cols-7 gap-2">
                        @foreach ($row as $cell)
                            @php
                                $isOverdueDay = $cell['date']->lt($today) && ($cell['counts']['todoTotal'] ?? 0) > 0;
                                $hasDone = ($cell['counts']['doneTotal'] ?? 0) > 0;
                                $isOverloadedDay = ($cell['counts']['todoTotal'] ?? 0) >= 8;
                                $monthCellClass = $cell['isSelected']
                                    ? 'border-slate-900 bg-slate-900 text-white'
                                    : ($isOverloadedDay
                                        ? 'border-fuchsia-300 bg-fuchsia-50/25 hover:bg-fuchsia-50/40'
                                        : ($isOverdueDay
                                        ? 'border-rose-200 bg-rose-50/25 hover:bg-rose-50/40'
                                        : ((($cell['counts']['todoTotal'] ?? 0) > 0)
                                            ? 'border-blue-200 bg-blue-50/25 hover:bg-blue-50/40'
                                            : ($hasDone
                                                ? 'border-emerald-200 bg-emerald-50/15 hover:bg-emerald-50/30'
                                                : 'border-slate-200 bg-white hover:bg-slate-50'))));
                            @endphp
                            <a href="{{ route('calendar.index', array_merge(request()->except('page'), ['date' => $cell['key'], 'view' => 'day'])) }}"
                               class="block min-h-24 rounded-xl border p-2 transition {{ $monthCellClass }} {{ ! $cell['isCurrentMonth'] ? 'opacity-60' : '' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold">{{ $cell['date']->format('j') }}</span>
                                    @if ($cell['isToday'])
                                        <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $cell['isSelected'] ? 'bg-white text-slate-900' : 'bg-slate-900 text-white' }}">dnes</span>
                                    @endif
                                </div>
                                <div class="mt-2 text-[11px] {{ $cell['isSelected'] ? 'text-slate-200' : 'text-slate-600' }}">Neudelano {{ $cell['counts']['todoTotal'] ?? 0 }}</div>
                                <div class="mt-1 space-y-0.5 text-[10px]">
                                    <div class="{{ $cell['isSelected'] ? 'text-amber-200' : 'text-amber-700' }}">FU {{ $cell['counts']['todoFollowUps'] ?? 0 }}</div>
                                    <div class="{{ $cell['isSelected'] ? 'text-emerald-200' : 'text-emerald-700' }}">SCH {{ $cell['counts']['todoMeetings'] ?? 0 }}</div>
                                    <div class="{{ $cell['isSelected'] ? 'text-slate-200' : 'text-slate-500' }}">Hotovo {{ $cell['counts']['doneTotal'] ?? 0 }}</div>
                                    @if ($isOverloadedDay)
                                        <div class="{{ $cell['isSelected'] ? 'text-fuchsia-200' : 'text-fuchsia-700' }}">Pretizeni 8+</div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">Agenda vybraneho dne: {{ $calendarDate->format('Y-m-d') }}</h2>
        <div class="text-sm text-slate-500">{{ $counts['total'] }} aktivit</div>
    </div>

    <div class="space-y-3">
        @forelse ($items as $item)
            @php
                $isFollowUp = $item['type'] === 'follow-up';
                $isOverdueItem = !empty($item['isOverdue']) && ! $calendarDate->isFuture();
                $containerClass = $isOverdueItem
                    ? 'border-rose-200 bg-rose-50/30'
                    : ($isFollowUp ? 'border-amber-200 bg-amber-50/30' : 'border-emerald-200 bg-emerald-50/30');
            @endphp
            <div class="rounded-xl border {{ $containerClass }} p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $isFollowUp ? 'bg-amber-100 text-amber-800 ring-amber-200' : 'bg-emerald-100 text-emerald-800 ring-emerald-200' }}">
                                {{ $isFollowUp ? 'follow-up' : 'schuzka' }}
                            </span>
                            <span class="text-sm font-semibold text-slate-900">{{ $item['at']?->format('H:i') ?? '--:--' }}</span>
                            <span class="text-sm font-medium text-slate-900">{{ $item['title'] }}</span>
                        </div>
                        <div class="mt-1 text-sm text-slate-600">{{ $item['subtitle'] }}</div>
                        @if ($isOverdueItem)
                            <div class="mt-1 text-xs font-medium text-rose-700">Po terminu / po case</div>
                        @endif
                        @if (!empty($item['note']))
                            <div class="mt-2 line-clamp-3 whitespace-pre-line text-sm text-slate-700">{{ $item['note'] }}</div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2" data-row-link-ignore>
                        @if ($isFollowUp)
                            <form method="POST" action="{{ route('follow-ups.quick-status', $item['model']) }}">
                                @csrf
                                <input type="hidden" name="status" value="done">
                                <button type="submit" class="rounded-md bg-amber-600 px-3 py-2 text-xs font-medium text-white">Hotovo</button>
                            </form>
                            <a href="{{ route('follow-ups.edit', $item['model']) }}" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-300">Presunout</a>
                        @else
                            <form method="POST" action="{{ route('meetings.quick-status', $item['model']) }}" class="js-inline-save-form flex items-center gap-2">
                                @csrf
                                <select name="status" class="js-inline-save-select rounded-md border-slate-300 py-1 text-xs" data-initial-value="{{ $item['model']->status }}">
                                    @foreach (['planned', 'confirmed', 'done', 'cancelled'] as $meetingStatus)
                                        <option value="{{ $meetingStatus }}" @selected($item['model']->status === $meetingStatus)>{{ $meetingStatus }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="js-inline-save-btn invisible rounded-md bg-slate-700 px-2 py-1 text-xs font-medium text-white">OK</button>
                            </form>
                        @endif
                        <a href="{{ $item['detail_url'] }}" class="text-xs text-slate-700 underline">Detail</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl bg-white p-8 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">
                Pro vybrany den nejsou zadne follow-upy ani schuzky.
            </div>
        @endforelse
    </div>
@endsection
