@extends('layouts.crm', ['title' => 'Kalendar | Call CRM'])

@section('content')
    @php
        $todayDate = now()->format('Y-m-d');
        $isToday = $calendarDate->isSameDay(now());
        $viewMode = $viewMode ?? 'week';

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

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Kalendar aktivit</h1>
            <p class="text-sm text-slate-600">Prehled follow-upu a schuzek v rezimu den / tyden / mesic. Nove firmy sem schvalne nepatri.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('calendar.index', array_merge(request()->except('date', 'page'), ['date' => $prevDate])) }}" class="rounded-md bg-slate-200 px-3 py-2 font-medium text-slate-700">Predchozi</a>
            <a href="{{ route('calendar.index', array_merge(request()->except('date', 'page'), ['date' => $todayDate])) }}" class="rounded-md {{ $isToday ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-700' }} px-3 py-2 font-medium">Dnes</a>
            <a href="{{ route('calendar.index', array_merge(request()->except('date', 'page'), ['date' => $nextDate])) }}" class="rounded-md bg-slate-200 px-3 py-2 font-medium text-slate-700">Dalsi</a>
        </div>
    </div>

    <form method="GET" action="{{ route('calendar.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-6">
        <div>
            <label for="date" class="block text-sm font-medium text-slate-700">Datum</label>
            <input id="date" name="date" type="date" value="{{ $calendarDate->format('Y-m-d') }}" class="mt-1 w-full rounded-md border-slate-300">
        </div>

        <div>
            <label for="view" class="block text-sm font-medium text-slate-700">Zobrazeni</label>
            <select id="view" name="view" class="mt-1 w-full rounded-md border-slate-300">
                <option value="day" @selected($viewMode === 'day')>Den</option>
                <option value="week" @selected($viewMode === 'week')>Tyden</option>
                <option value="month" @selected($viewMode === 'month')>Mesic</option>
            </select>
        </div>

        @if ($isManager)
            <div>
                <label for="assigned_user_id" class="block text-sm font-medium text-slate-700">Uzivatel (agenda)</label>
                <select id="assigned_user_id" name="assigned_user_id" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Vse</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['assigned_user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="{{ $isManager ? '' : 'md:col-span-2' }}">
            <label class="block text-sm font-medium text-slate-700">Rozsah</label>
            @if ($isManager)
                <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="mine" value="0">
                    <input type="checkbox" name="mine" value="1" class="rounded border-slate-300" @checked(($filters['mine'] ?? '1') === '1')>
                    <span>Jen moje agenda</span>
                </label>
            @else
                <input type="hidden" name="mine" value="1">
                <p class="mt-2 text-sm text-slate-500">Zobrazuje se jen vase agenda.</p>
            @endif
        </div>

        <div class="md:col-span-2 flex items-end gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Zobrazit</button>
            <a href="{{ route('calendar.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Reset</a>
        </div>
    </form>

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <div class="text-xs text-slate-500">Vybrany den (agenda dole)</div>
            <div class="mt-1 text-2xl font-semibold">{{ $counts['total'] }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ $calendarDate->format('Y-m-d') }}</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-blue-200">
            <div class="text-xs text-blue-700">Aktivity v zobrazenem obdobi</div>
            <div class="mt-1 text-2xl font-semibold text-blue-800">{{ $rangeCounts['total'] ?? $counts['total'] }}</div>
            <div class="mt-1 text-xs text-slate-500">follow-upy + schuzky</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <div class="text-xs text-slate-500">Dnes (vybrany den)</div>
            <div class="mt-1 flex gap-4 text-sm">
                <span><span class="font-semibold text-amber-700">FU</span>: {{ $counts['followUps'] }}</span>
                <span><span class="font-semibold text-emerald-700">SCH</span>: {{ $counts['meetings'] }}</span>
            </div>
        </div>
    </div>

    <div class="mb-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        @if (($calendarGrid['type'] ?? '') === 'day')
            @php $cell = $calendarGrid['days'][0] ?? null; @endphp
            @if ($cell)
                <div class="rounded-xl border p-4 {{ $cell['counts']['total'] > 0 ? 'border-blue-200 bg-blue-50/30' : 'border-slate-200 bg-slate-50/40' }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold">{{ $cell['date']->format('l') }} {{ $cell['date']->format('Y-m-d') }}</div>
                            <div class="text-xs text-slate-500">Denni souhrn aktivit</div>
                        </div>
                        <a href="{{ route('calendar.index', array_merge(request()->except('date'), ['date' => $cell['key'], 'view' => 'day'])) }}" class="text-xs text-slate-600 underline">Obnovit den</a>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                            <div class="text-xs text-slate-500">Celkem</div>
                            <div class="mt-1 text-xl font-semibold">{{ $cell['counts']['total'] }}</div>
                        </div>
                        <div class="rounded-lg bg-amber-50 p-3 ring-1 ring-amber-200">
                            <div class="text-xs text-amber-700">Follow-upy</div>
                            <div class="mt-1 text-xl font-semibold text-amber-800">{{ $cell['counts']['followUps'] }}</div>
                        </div>
                        <div class="rounded-lg bg-emerald-50 p-3 ring-1 ring-emerald-200">
                            <div class="text-xs text-emerald-700">Schuzky</div>
                            <div class="mt-1 text-xl font-semibold text-emerald-800">{{ $cell['counts']['meetings'] }}</div>
                        </div>
                    </div>
                </div>
            @endif
        @elseif (($calendarGrid['type'] ?? '') === 'week')
            <div class="grid gap-3 md:grid-cols-7">
                @foreach ($calendarGrid['days'] as $index => $cell)
                    <a href="{{ route('calendar.index', array_merge(request()->except('page'), ['date' => $cell['key'], 'view' => 'week'])) }}"
                       class="block rounded-xl border p-3 transition {{ $cell['isSelected'] ? 'border-slate-900 bg-slate-900 text-white' : ($cell['counts']['total'] > 0 ? 'border-blue-200 bg-blue-50/30 hover:bg-blue-50/50' : 'border-slate-200 bg-slate-50/40 hover:bg-slate-50') }}">
                        <div class="text-xs {{ $cell['isSelected'] ? 'text-slate-200' : 'text-slate-500' }}">{{ $dayHeaders[$index] }}</div>
                        <div class="mt-1 text-lg font-semibold">{{ $cell['date']->format('j.n.') }}</div>
                        <div class="mt-2 text-xs {{ $cell['isSelected'] ? 'text-slate-200' : 'text-slate-600' }}">Celkem: {{ $cell['counts']['total'] }}</div>
                        <div class="mt-1 flex gap-2 text-[11px]">
                            <span class="{{ $cell['isSelected'] ? 'text-amber-200' : 'text-amber-700' }}">FU {{ $cell['counts']['followUps'] }}</span>
                            <span class="{{ $cell['isSelected'] ? 'text-emerald-200' : 'text-emerald-700' }}">SCH {{ $cell['counts']['meetings'] }}</span>
                        </div>
                    </a>
                @endforeach
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
                            <a href="{{ route('calendar.index', array_merge(request()->except('page'), ['date' => $cell['key'], 'view' => 'month'])) }}"
                               class="block min-h-24 rounded-xl border p-2 transition {{ $cell['isSelected'] ? 'border-slate-900 bg-slate-900 text-white' : ($cell['counts']['total'] > 0 ? 'border-blue-200 bg-blue-50/25 hover:bg-blue-50/40' : 'border-slate-200 bg-white hover:bg-slate-50') }} {{ ! $cell['isCurrentMonth'] ? 'opacity-60' : '' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold">{{ $cell['date']->format('j') }}</span>
                                    @if ($cell['isToday'])
                                        <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $cell['isSelected'] ? 'bg-white text-slate-900' : 'bg-slate-900 text-white' }}">dnes</span>
                                    @endif
                                </div>
                                <div class="mt-2 text-[11px] {{ $cell['isSelected'] ? 'text-slate-200' : 'text-slate-600' }}">{{ $cell['counts']['total'] }} aktivit</div>
                                <div class="mt-1 space-y-0.5 text-[10px]">
                                    <div class="{{ $cell['isSelected'] ? 'text-amber-200' : 'text-amber-700' }}">FU {{ $cell['counts']['followUps'] }}</div>
                                    <div class="{{ $cell['isSelected'] ? 'text-emerald-200' : 'text-emerald-700' }}">SCH {{ $cell['counts']['meetings'] }}</div>
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
                $containerClass = $isFollowUp
                    ? 'border-amber-200 bg-amber-50/30'
                    : 'border-emerald-200 bg-emerald-50/30';
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
