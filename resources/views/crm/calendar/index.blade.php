@extends('layouts.crm', ['title' => 'Kalendar | Call CRM'])

@section('content')
    @php
        $prevDate = $calendarDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $calendarDate->copy()->addDay()->format('Y-m-d');
        $todayDate = now()->format('Y-m-d');
        $isToday = $calendarDate->isSameDay(now());
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Kalendar aktivit</h1>
            <p class="text-sm text-slate-600">Dnesni agenda follow-upu a schuzek. Nove firmy sem schvalne nepatri.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('calendar.index', array_merge(request()->except('date', 'page'), ['date' => $prevDate])) }}" class="rounded-md bg-slate-200 px-3 py-2 font-medium text-slate-700">Predchozi den</a>
            <a href="{{ route('calendar.index', array_merge(request()->except('date', 'page'), ['date' => $todayDate])) }}" class="rounded-md {{ $isToday ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-700' }} px-3 py-2 font-medium">Dnes</a>
            <a href="{{ route('calendar.index', array_merge(request()->except('date', 'page'), ['date' => $nextDate])) }}" class="rounded-md bg-slate-200 px-3 py-2 font-medium text-slate-700">Dalsi den</a>
        </div>
    </div>

    <form method="GET" action="{{ route('calendar.index') }}" class="mb-6 grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-5">
        <div>
            <label for="date" class="block text-sm font-medium text-slate-700">Datum</label>
            <input id="date" name="date" type="date" value="{{ $calendarDate->format('Y-m-d') }}" class="mt-1 w-full rounded-md border-slate-300">
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
            <div class="text-xs text-slate-500">Celkem aktivit</div>
            <div class="mt-1 text-2xl font-semibold">{{ $counts['total'] }}</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-amber-200">
            <div class="text-xs text-amber-700">Follow-upy</div>
            <div class="mt-1 text-2xl font-semibold text-amber-800">{{ $counts['followUps'] }}</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-emerald-200">
            <div class="text-xs text-emerald-700">Schuzky</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-800">{{ $counts['meetings'] }}</div>
        </div>
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
