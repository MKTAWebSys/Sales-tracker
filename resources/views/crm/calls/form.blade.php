@php
    $flowMode = $flowMode ?? ($call->exists ? 'edit' : 'create');
    $isFinishFlow = $flowMode === 'finish';
    $isActiveCall = $call->outcome === 'pending' && ! $call->ended_at;
    $finalizeCall = ($finalizeCall ?? false) || ! $isFinishFlow || $call->ended_at || $call->outcome !== 'pending';
    $isActiveNoteOnlyFinish = $isFinishFlow && ! $finalizeCall && $isActiveCall;
    $isCallerMode = request()->boolean('caller_mode');
    $isCallerFinalizeMinimal = $isFinishFlow && $finalizeCall && $isCallerMode;
    $isCreateFlow = $flowMode === 'create';
    $titleText = $isFinishFlow
        ? ($isActiveNoteOnlyFinish ? 'Probiha hovor' : 'Hovor ukoncen')
        : ($call->exists ? 'Upravit hovor' : 'Novy hovor');
    $submitText = 'Ulozit';
    $company = $call->company ?? $companies->firstWhere('id', $call->company_id);
    $finishOutcomeDefault = ($isFinishFlow && $finalizeCall && $call->outcome === 'pending')
        ? 'callback'
        : ($call->outcome ?: 'callback');
@endphp

@extends('layouts.crm', ['title' => $titleText . ' | Call CRM'])

@section('content')
    @if ($isFinishFlow && $finalizeCall)
        @php
            $startAt = old('called_at') ? \Illuminate\Support\Carbon::parse(old('called_at')) : $call->called_at;
            $endAt = old('ended_at') ? \Illuminate\Support\Carbon::parse(old('ended_at')) : $call->ended_at;
            $durationMinutes = ($startAt && $endAt && $endAt->greaterThanOrEqualTo($startAt))
                ? $startAt->diffInMinutes($endAt)
                : null;
        @endphp
        <div class="mb-3 rounded-2xl border border-slate-200 bg-gradient-to-b from-slate-900 to-slate-800 p-3 text-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-300">Hovor ukoncen</div>
                    <div class="mt-0.5 text-base font-semibold text-white/95">{{ $company?->name ?? 'Bez firmy' }}</div>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-slate-300">
                        <span>Od: {{ $startAt?->format('Y-m-d H:i:s') ?? '-' }}</span>
                        <span>Do: {{ $endAt?->format('Y-m-d H:i:s') ?? '-' }}</span>
                    </div>
                </div>
                <div class="rounded-full bg-white/10 px-3 py-1 text-sm font-semibold tabular-nums text-white ring-1 ring-white/10">
                    {{ $durationMinutes !== null ? $durationMinutes.' min' : '-' }}
                </div>
            </div>
        </div>
    @endif

    <div class="{{ $isActiveNoteOnlyFinish ? '-mt-3 sm:-mt-4' : '' }}">
    @if (! ($isFinishFlow && $finalizeCall) && ! $isActiveNoteOnlyFinish)
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">{{ $titleText }}</h1>
            @if ($isFinishFlow)
                <p class="text-sm text-slate-600">
                    Aktivni hovor: zatim zapisuj jen poznamku. Vysledek a dalsi kroky vyberes az pri ukonceni hovoru.
                </p>
            @else
                <p class="text-sm text-slate-600">Zaznam hovoru s poli pro navazujici kroky.</p>
                <p class="mt-1 text-xs text-slate-500">Pokud vyplnis follow-up / schuzku / predani, navazane zaznamy se po ulozeni vytvori automaticky.</p>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ $call->exists ? route('calls.update', $call) : route('calls.store') }}" class="js-call-flow-form {{ $isActiveNoteOnlyFinish ? 'space-y-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6 pb-28 sm:pb-6' : 'space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200' }}">
        @csrf
        @if ($call->exists)
            @method('PUT')
        @endif
        <input type="hidden" name="flow_mode" value="{{ $flowMode }}">
        @if ($isFinishFlow && $finalizeCall)
            <input type="hidden" name="finalize_call" value="1">
        @endif
        @if ($isCallerMode)
            <input type="hidden" name="caller_mode" value="1">
        @endif
        @if ($isFinishFlow && $finalizeCall)
            <input type="hidden" name="ended_at" value="{{ old('ended_at', optional($call->ended_at)->format('Y-m-d\\TH:i:s')) }}">
            <input type="hidden" name="company_id" value="{{ old('company_id', $call->company_id) }}">
            <input type="hidden" name="called_at" value="{{ old('called_at', optional($call->called_at)->format('Y-m-d\\TH:i:s')) }}">
        @endif

        @if ($isFinishFlow && $finalizeCall)
            @error('ended_at')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
        @endif

        @if ($isActiveNoteOnlyFinish)
            <input type="hidden" name="company_id" value="{{ old('company_id', $call->company_id) }}">
            <input type="hidden" name="called_at" value="{{ old('called_at', optional($call->called_at)->format('Y-m-d\\TH:i:s')) }}">
            <input type="hidden" name="outcome" value="pending">

            <div class="rounded-3xl border border-slate-700/70 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-800 p-6 text-white shadow-xl ring-1 ring-white/10 sm:p-8">
                <div class="text-center">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-300/90">Probiha hovor</div>
                    <div class="mt-4 text-5xl font-semibold tabular-nums tracking-tight sm:text-6xl js-call-live-timer" data-called-at="{{ $call->called_at?->toIso8601String() ?? '' }}">
                        00:00
                    </div>
                    <div class="mt-4 text-xl font-medium text-white/95 sm:text-2xl">{{ $company?->name ?? 'Bez firmy' }}</div>
                    <div class="mt-2 inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs text-slate-200 ring-1 ring-white/10">
                        Start {{ $call->called_at?->format('Y-m-d H:i:s') ?? '-' }}
                    </div>
                </div>
            </div>
        @else
            @if (! ($isFinishFlow && $finalizeCall))
            <div>
                <label for="company_id" class="block text-sm font-medium text-slate-700">Firma</label>
                <select id="company_id" name="company_id" required class="mt-1 w-full rounded-md border-slate-300" @disabled($isFinishFlow)>
                    <option value="">Vyberte firmu</option>
                    @foreach ($companies as $companyOption)
                        <option value="{{ $companyOption->id }}" @selected((string) old('company_id', $call->company_id) === (string) $companyOption->id)>
                            {{ $companyOption->name }}
                        </option>
                    @endforeach
                </select>
                @if ($isFinishFlow)
                    <input type="hidden" name="company_id" value="{{ old('company_id', $call->company_id) }}">
                @endif
                @error('company_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <div class="{{ $isFinishFlow && $finalizeCall ? 'rounded-xl border border-slate-200 bg-white p-3 shadow-sm ring-1 ring-slate-200 lg:grid lg:grid-cols-[minmax(0,40%)_minmax(0,60%)] lg:gap-4' : 'grid gap-6 sm:grid-cols-2' }}">
                @if (! ($isFinishFlow && $finalizeCall))
                    <div>
                        <label for="called_at" class="block text-sm font-medium text-slate-700">{{ $isFinishFlow ? 'Cas startu hovoru' : 'Datum a cas hovoru' }}</label>
                        <input id="called_at" name="called_at" type="datetime-local" required value="{{ old('called_at', optional($call->called_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300" @readonly($isFinishFlow)>
                        @error('called_at')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
                <div class="{{ $isFinishFlow && $finalizeCall ? 'lg:pr-1' : '' }}">
                    <label class="block text-sm font-medium text-slate-700">{{ $isFinishFlow && $finalizeCall ? 'Vysledek hovoru' : 'Vysledek' }}</label>
                    <select id="outcome" name="outcome" class="js-call-outcome sr-only">
                        @foreach (($isFinishFlow && $finalizeCall ? ['no-answer', 'callback', 'interested', 'not-interested', 'meeting-booked'] : ['pending', 'no-answer', 'callback', 'interested', 'not-interested', 'meeting-booked']) as $outcome)
                            <option value="{{ $outcome }}" @selected(old('outcome', $finishOutcomeDefault) === $outcome)>
                                @switch($outcome)
                                    @case('pending') Rozpracovano @break
                                    @case('no-answer') Nezastizen @break
                                    @case('callback') Zavolat znovu @break
                                    @case('interested') Zajem @break
                                    @case('not-interested') Bez zajmu @break
                                    @case('meeting-booked') Schuzka domluvena @break
                                    @default {{ $outcome }}
                                @endswitch
                            </option>
                        @endforeach
                    </select>
                    @php
                        $outcomeLabels = [
                            'pending' => 'Rozpracovano',
                            'no-answer' => 'Nezastizen',
                            'callback' => 'Zavolat znovu',
                            'interested' => 'Zajem',
                            'not-interested' => 'Bez zajmu',
                            'meeting-booked' => 'Schuzka domluvena',
                        ];
                    @endphp
                    <div class="mt-1.5 grid grid-cols-1 gap-1.5 {{ $isFinishFlow && $finalizeCall ? 'max-w-[25rem]' : 'sm:grid-cols-2' }}">
                        @foreach (($isFinishFlow && $finalizeCall ? ['no-answer', 'callback', 'interested', 'not-interested', 'meeting-booked'] : ['pending', 'no-answer', 'callback', 'interested', 'not-interested', 'meeting-booked']) as $outcome)
                            @php
                                $isSelectedOutcome = old('outcome', $finishOutcomeDefault) === $outcome;
                                $chipTone = match ($outcome) {
                                    'meeting-booked' => 'emerald',
                                    'interested' => 'blue',
                                    'callback', 'no-answer' => 'amber',
                                    'not-interested' => 'rose',
                                    default => 'slate',
                                };
                            @endphp
                            <button
                                type="button"
                                class="js-call-outcome-chip rounded-xl border text-sm font-medium transition {{ $isFinishFlow && $finalizeCall ? 'px-3 py-1 text-center' : 'px-3 py-3 text-left' }} {{ $isSelectedOutcome ? 'ring-2 ' : '' }} {{ $chipTone === 'emerald' ? ($isSelectedOutcome ? 'border-emerald-300 bg-emerald-100 text-emerald-900 ring-emerald-300' : ($isFinishFlow && $finalizeCall ? 'border-emerald-200 bg-emerald-50 text-emerald-900 hover:bg-emerald-100' : 'border-slate-200 bg-white text-slate-800 hover:border-slate-300 hover:bg-slate-50')) : '' }} {{ $chipTone === 'blue' ? ($isSelectedOutcome ? 'border-blue-300 bg-blue-100 text-blue-900 ring-blue-300' : ($isFinishFlow && $finalizeCall ? 'border-blue-200 bg-blue-50 text-blue-900 hover:bg-blue-100' : 'border-slate-200 bg-white text-slate-800 hover:border-slate-300 hover:bg-slate-50')) : '' }} {{ $chipTone === 'amber' ? ($isSelectedOutcome ? 'border-amber-300 bg-amber-100 text-amber-900 ring-amber-300' : ($isFinishFlow && $finalizeCall ? 'border-amber-200 bg-amber-50 text-amber-900 hover:bg-amber-100' : 'border-slate-200 bg-white text-slate-800 hover:border-slate-300 hover:bg-slate-50')) : '' }} {{ $chipTone === 'rose' ? ($isSelectedOutcome ? 'border-rose-300 bg-rose-100 text-rose-900 ring-rose-300' : ($isFinishFlow && $finalizeCall ? 'border-rose-200 bg-rose-50 text-rose-900 hover:bg-rose-100' : 'border-slate-200 bg-white text-slate-800 hover:border-slate-300 hover:bg-slate-50')) : '' }} {{ $chipTone === 'slate' ? ($isSelectedOutcome ? 'border-slate-300 bg-slate-200 text-slate-900 ring-slate-300' : ($isFinishFlow && $finalizeCall ? 'border-slate-200 bg-slate-100 text-slate-900 hover:bg-slate-200' : 'border-slate-200 bg-white text-slate-800 hover:border-slate-300 hover:bg-slate-50')) : '' }}"
                                data-outcome-value="{{ $outcome }}"
                                aria-pressed="{{ $isSelectedOutcome ? 'true' : 'false' }}"
                            >
                                {{ $outcomeLabels[$outcome] ?? $outcome }}
                            </button>
                        @endforeach
                    </div>
                    @error('outcome')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($isFinishFlow && $finalizeCall)
                    <div class="mt-3 lg:mt-0">
                        <label for="summary" class="block text-sm font-medium text-slate-700">Poznamka / shrnuti hovoru</label>
                        <textarea id="summary" name="summary" rows="4" class="mt-1 w-full rounded-md border-slate-300" autofocus>{{ old('summary', $call->summary) }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">Poznamku muzes psat prubezne behem hovoru. Dalsi kroky se jen prizpusobi podle vysledku.</p>
                        @error('summary')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>
        @endif

        @if ($isFinishFlow && ! $finalizeCall)
            <div>
                <label for="summary" class="block text-sm font-medium text-slate-700">Poznamka / shrnuti hovoru</label>
                <textarea id="summary" name="summary" rows="{{ $isActiveNoteOnlyFinish ? '10' : '6' }}" class="mt-1 w-full rounded-md border-slate-300" autofocus>{{ old('summary', $call->summary) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Poznamku muzes psat prubezne behem hovoru. Dalsi kroky se jen prizpusobi podle vysledku.</p>
                @error('summary')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        @if ($isFinishFlow && $finalizeCall)
            <div class="js-callback-presets hidden rounded-xl border border-amber-200 bg-amber-50/70 p-3">
                    <div class="mb-2 text-xs font-medium uppercase tracking-wide text-amber-700">Rychly termin pro znovu zavolat</div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="js-followup-preset rounded-md bg-amber-100 px-3 py-2 text-sm font-medium text-amber-900 ring-1 ring-amber-200" data-preset="today_afternoon">
                            Dnes odpoledne (15:00)
                        </button>
                        <button type="button" class="js-followup-preset rounded-md bg-amber-100 px-3 py-2 text-sm font-medium text-amber-900 ring-1 ring-amber-200" data-preset="tomorrow_morning">
                            Zitra dopoledne (09:00)
                        </button>
                        <button type="button" class="js-followup-preset rounded-md bg-amber-100 px-3 py-2 text-sm font-medium text-amber-900 ring-1 ring-amber-200" data-preset="tomorrow_afternoon">
                            Zitra odpoledne (15:00)
                        </button>
                        <div class="flex items-center gap-2 rounded-md bg-white/70 px-2 py-1 ring-1 ring-amber-200">
                            <input id="next_follow_up_date_quick" type="date" class="js-followup-quick-date rounded-md border-amber-200 bg-white px-2 py-1 text-xs text-slate-900">
                            <input id="next_follow_up_time_quick" type="time" class="js-followup-quick-time rounded-md border-amber-200 bg-white px-2 py-1 text-xs text-slate-900" step="60">
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-slate-500">
                        Zkratky: <kbd class="rounded bg-slate-200 px-1.5 py-0.5">Alt+1</kbd>,
                        <kbd class="rounded bg-slate-200 px-1.5 py-0.5">Alt+2</kbd>,
                        <kbd class="rounded bg-slate-200 px-1.5 py-0.5">Alt+3</kbd>
                    </div>
            </div>
        @endif

        @if (! $isActiveNoteOnlyFinish)
            @if ($isFinishFlow && $finalizeCall)
                <div class="space-y-3">
                    <details class="js-call-panel js-panel-followup rounded-xl border border-slate-200 bg-white p-3" data-show-for="callback,no-answer,interested" @open(in_array(old('outcome', $finishOutcomeDefault), ['callback','no-answer','interested'], true))>
                        <summary class="cursor-pointer list-none text-sm font-semibold text-slate-800">
                            Follow-up
                            <span class="ml-2 text-xs font-normal text-slate-500">naplanovat dalsi kontakt</span>
                        </summary>
                        <div class="mt-3">
                            <label for="next_follow_up_at" class="block text-sm font-medium text-slate-700">Dalsi follow-up</label>
                            <input id="next_follow_up_at" name="next_follow_up_at" type="datetime-local" value="{{ old('next_follow_up_at', optional($call->next_follow_up_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                            @error('next_follow_up_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </details>

                    <details class="js-call-panel js-panel-meeting rounded-xl border border-slate-200 bg-white p-3" data-show-for="meeting-booked,interested" @open(in_array(old('outcome', $finishOutcomeDefault), ['meeting-booked','interested'], true))>
                        <summary class="cursor-pointer list-none text-sm font-semibold text-slate-800">
                            Schuzka
                            <span class="ml-2 text-xs font-normal text-slate-500">volitelne naplanovani</span>
                        </summary>
                        <div class="mt-3">
                            <label for="meeting_planned_at" class="block text-sm font-medium text-slate-700">Planovana schuzka</label>
                            <input id="meeting_planned_at" name="meeting_planned_at" type="datetime-local" value="{{ old('meeting_planned_at', optional($call->meeting_planned_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                            @error('meeting_planned_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </details>

                    @if (! $isCallerFinalizeMinimal)
                        <details class="js-call-panel js-panel-handover rounded-xl border border-slate-200 bg-white p-3" data-show-for="interested,meeting-booked">
                            <summary class="cursor-pointer list-none text-sm font-semibold text-slate-800">
                                Predani leadu
                                <span class="ml-2 text-xs font-normal text-slate-500">volitelne</span>
                            </summary>
                            <div class="mt-3">
                                <label for="handed_over_to_id" class="block text-sm font-medium text-slate-700">Predat komu</label>
                                <select id="handed_over_to_id" name="handed_over_to_id" class="mt-1 w-full rounded-md border-slate-300">
                                    <option value="">Bez predani</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected((string) old('handed_over_to_id', $call->handed_over_to_id) === (string) $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('handed_over_to_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </details>
                    @endif
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="js-call-panel js-panel-followup" data-show-for="callback,no-answer,interested">
                        <label for="next_follow_up_at" class="block text-sm font-medium text-slate-700">Dalsi follow-up</label>
                        <input id="next_follow_up_at" name="next_follow_up_at" type="datetime-local" value="{{ old('next_follow_up_at', optional($call->next_follow_up_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                        <p class="mt-1 text-xs text-slate-500">Volitelne. Pro callback/no-answer se casto hodi rychly termin vyse.</p>
                        @error('next_follow_up_at')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="js-call-panel js-panel-meeting" data-show-for="meeting-booked,interested">
                        <label for="meeting_planned_at" class="block text-sm font-medium text-slate-700">Planovana schuzka</label>
                        <input id="meeting_planned_at" name="meeting_planned_at" type="datetime-local" value="{{ old('meeting_planned_at', optional($call->meeting_planned_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                        @error('meeting_planned_at')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="js-call-panel js-panel-handover" data-show-for="interested,meeting-booked">
                        <label for="handed_over_to_id" class="block text-sm font-medium text-slate-700">Predat komu</label>
                        <select id="handed_over_to_id" name="handed_over_to_id" class="mt-1 w-full rounded-md border-slate-300">
                            <option value="">Bez predani</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((string) old('handed_over_to_id', $call->handed_over_to_id) === (string) $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('handed_over_to_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="js-call-panel js-panel-status hidden" data-show-for=""></div>
                </div>
            @endif
        @endif

        @unless ($isFinishFlow)
            <div>
                <label for="summary" class="block text-sm font-medium text-slate-700">Poznamka / shrnuti hovoru</label>
                <textarea id="summary" name="summary" rows="6" class="mt-1 w-full rounded-md border-slate-300">{{ old('summary', $call->summary) }}</textarea>
                @error('summary')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endunless

        <div class="flex flex-wrap items-center gap-3">
            @if ($isActiveNoteOnlyFinish)
                <form method="POST" action="{{ route('calls.end', $call) }}" class="js-active-call-end-form w-full sm:w-auto">
                    @csrf
                    @if ($isCallerMode)
                        <input type="hidden" name="caller_mode" value="1">
                    @endif
                    <button type="submit" class="hidden w-full items-center justify-center rounded-md bg-rose-600 px-4 py-3 text-sm font-semibold text-white hover:bg-rose-700 sm:inline-flex sm:min-w-[15rem]">
                        Ukoncit hovor
                    </button>
                </form>
            @else
                <button type="submit" class="rounded-md {{ $isFinishFlow ? 'bg-emerald-600' : 'bg-slate-900' }} px-4 py-2 text-sm font-medium text-white">{{ $submitText }}</button>
            @endif
            @if ($isFinishFlow && ! $isCallerMode && ! $isActiveNoteOnlyFinish)
                <button type="submit" name="submit_action" value="save_next_company" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white">
                    Ulozit a dalsi
                </button>
            @endif
        </div>
    </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.js-call-flow-form');
            if (!form) return;

            const outcomeSelect = form.querySelector('.js-call-outcome');
            const outcomeChips = Array.from(form.querySelectorAll('.js-call-outcome-chip'));
            const panels = Array.from(form.querySelectorAll('.js-call-panel'));
            const callbackPresetsWrap = form.querySelector('.js-callback-presets');
            const followUpInput = form.querySelector('#next_follow_up_at');
            const followUpQuickDate = form.querySelector('.js-followup-quick-date');
            const followUpQuickTime = form.querySelector('.js-followup-quick-time');
            const meetingInput = form.querySelector('#meeting_planned_at');
            const summaryInput = form.querySelector('#summary');
            const presetButtons = Array.from(form.querySelectorAll('.js-followup-preset'));
            const isFinishFlow = {{ $isFinishFlow ? 'true' : 'false' }};
            const finalizeCall = {{ $finalizeCall ? 'true' : 'false' }};
            const isActiveNoteOnlyFinish = {{ $isActiveNoteOnlyFinish ? 'true' : 'false' }};
            const callId = {{ $call->exists ? (int) $call->id : 'null' }};
            const draftKey = callId ? ('call-finish-summary-draft:' + String(callId)) : null;
            let autosaveTimer = null;
            const liveTimer = form.querySelector('.js-call-live-timer');
            const quickEndForm = form.querySelector('.js-active-call-end-form');

            const formatDuration = function (totalSeconds) {
                const seconds = Math.max(0, totalSeconds | 0);
                const hrs = Math.floor(seconds / 3600);
                const mins = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;
                const pad = (n) => String(n).padStart(2, '0');
                return hrs > 0 ? (pad(hrs) + ':' + pad(mins) + ':' + pad(secs)) : (pad(mins) + ':' + pad(secs));
            };

            if (liveTimer) {
                const calledAtIso = String(liveTimer.getAttribute('data-called-at') || '');
                if (calledAtIso) {
                    const startedAt = new Date(calledAtIso);
                    const tick = function () {
                        const diffSeconds = Math.floor((Date.now() - startedAt.getTime()) / 1000);
                        liveTimer.textContent = formatDuration(diffSeconds);
                    };
                    tick();
                    window.setInterval(tick, 1000);
                }
            }

            const setDateValue = function (input, date) {
                if (!input) return;
                const local = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
                input.value = local.toISOString().slice(0, 16);
            };

            const syncFollowUpQuickInput = function () {
                if (!followUpInput || !followUpQuickDate || !followUpQuickTime) return;
                const value = String(followUpInput.value || '').trim();
                if (!value) {
                    followUpQuickDate.value = '';
                    followUpQuickTime.value = '';
                    return;
                }

                const parts = value.split('T');
                followUpQuickDate.value = parts[0] || '';
                followUpQuickTime.value = (parts[1] || '').slice(0, 5);
            };

            const hasValue = function (input) {
                return !!(input && String(input.value || '').trim() !== '');
            };

            const nextBusinessTime = function (daysAhead, hour) {
                const target = new Date();
                target.setDate(target.getDate() + daysAhead);
                target.setHours(hour, 0, 0, 0);
                return target;
            };

            const applyPreset = function (preset) {
                if (!followUpInput) return;

                const now = new Date();
                const target = new Date();

                if (preset === 'today_afternoon') {
                    target.setHours(15, 0, 0, 0);
                    if (target <= now) {
                        target.setDate(target.getDate() + 1);
                    }
                }
                if (preset === 'tomorrow_morning') {
                    target.setDate(target.getDate() + 1);
                    target.setHours(9, 0, 0, 0);
                }
                if (preset === 'tomorrow_afternoon') {
                    target.setDate(target.getDate() + 1);
                    target.setHours(15, 0, 0, 0);
                }

                setDateValue(followUpInput, target);
                syncFollowUpQuickInput();

            };

            const applySmartDefaults = function (outcome) {
                if (!isFinishFlow || !finalizeCall) return;

                if ((outcome === 'callback' || outcome === 'no-answer') && !hasValue(followUpInput)) {
                    const now = new Date();
                    const target = new Date();
                    if (outcome === 'no-answer') {
                        target.setHours(15, 0, 0, 0);
                        if (target <= now) {
                            target.setDate(target.getDate() + 1);
                            target.setHours(9, 0, 0, 0);
                        }
                    } else {
                        target.setDate(target.getDate() + 1);
                        target.setHours(9, 0, 0, 0);
                    }
                    setDateValue(followUpInput, target);
                    syncFollowUpQuickInput();
                }

                if (outcome === 'interested') {
                    if (!hasValue(followUpInput)) {
                        setDateValue(followUpInput, nextBusinessTime(2, 10));
                        syncFollowUpQuickInput();
                    }
                }

                if (outcome === 'meeting-booked') {
                    if (!hasValue(meetingInput)) {
                        setDateValue(meetingInput, nextBusinessTime(1, 10));
                    }
                }
            };

            const updatePanels = function () {
                const outcome = outcomeSelect ? String(outcomeSelect.value || '') : '';

                panels.forEach((panel) => {
                    const showFor = String(panel.getAttribute('data-show-for') || '')
                        .split(',')
                        .map((value) => value.trim())
                        .filter(Boolean);

                    const visible = !isFinishFlow || !finalizeCall || showFor.length === 0 || showFor.includes(outcome);
                    panel.classList.toggle('hidden', !visible);
                });

                if (callbackPresetsWrap) {
                    const showPresets = isFinishFlow && finalizeCall && (outcome === 'callback' || outcome === 'no-answer');
                    callbackPresetsWrap.classList.toggle('hidden', !showPresets);
                    if (showPresets) {
                        syncFollowUpQuickInput();
                    }
                }

                applySmartDefaults(outcome);
            };

            const updateOutcomeChipState = function () {
                if (!outcomeSelect || outcomeChips.length === 0) return;
                const current = String(outcomeSelect.value || '');
                outcomeChips.forEach((chip) => {
                    const value = String(chip.getAttribute('data-outcome-value') || '');
                    const active = value === current;
                    chip.setAttribute('aria-pressed', active ? 'true' : 'false');
                    chip.classList.toggle('ring-2', active);
                });
            };

            if (outcomeSelect) {
                outcomeSelect.addEventListener('change', updatePanels);
                outcomeSelect.addEventListener('change', updateOutcomeChipState);
                updatePanels();
                updateOutcomeChipState();
            }

            outcomeChips.forEach((chip) => {
                chip.addEventListener('click', function () {
                    if (!outcomeSelect) return;
                    const value = String(chip.getAttribute('data-outcome-value') || '');
                    if (!value) return;
                    outcomeSelect.value = value;
                    outcomeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });

            presetButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    applyPreset(String(button.getAttribute('data-preset') || ''));
                });
            });

            const syncMainFollowUpFromQuick = function () {
                if (!followUpInput || !followUpQuickDate || !followUpQuickTime) return;
                const date = String(followUpQuickDate.value || '').trim();
                const time = String(followUpQuickTime.value || '').trim();
                if (!date) return;
                followUpInput.value = time ? (date + 'T' + time) : (date + 'T09:00');
            };

            if (followUpQuickDate && followUpQuickTime && followUpInput) {
                followUpInput.addEventListener('change', syncFollowUpQuickInput);
                followUpInput.addEventListener('input', syncFollowUpQuickInput);
                followUpQuickDate.addEventListener('change', syncMainFollowUpFromQuick);
                followUpQuickTime.addEventListener('change', syncMainFollowUpFromQuick);
            }

            if (isFinishFlow && summaryInput && draftKey) {
                try {
                    const existingDraft = window.localStorage.getItem(draftKey);
                    if (existingDraft && !String(summaryInput.value || '').trim()) {
                        summaryInput.value = existingDraft;
                    }
                } catch (error) {
                }

                summaryInput.addEventListener('input', function () {
                    window.clearTimeout(autosaveTimer);
                    autosaveTimer = window.setTimeout(function () {
                        try {
                            window.localStorage.setItem(draftKey, String(summaryInput.value || ''));
                        } catch (error) {
                        }
                    }, 300);
                });

                form.addEventListener('submit', function () {
                    try {
                        window.localStorage.removeItem(draftKey);
                    } catch (error) {
                    }
                });
            }

            if (summaryInput && isActiveNoteOnlyFinish) {
                summaryInput.focus();
                summaryInput.setSelectionRange(summaryInput.value.length, summaryInput.value.length);
            }

            if (summaryInput && isActiveNoteOnlyFinish) {
                summaryInput.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') return;
                    if (!event.ctrlKey && !event.metaKey) return;
                    if (!quickEndForm) return;
                    event.preventDefault();
                    quickEndForm.requestSubmit();
                });
            }

            document.addEventListener('keydown', function (event) {
                if (!isFinishFlow || !finalizeCall || !callbackPresetsWrap || callbackPresetsWrap.classList.contains('hidden')) return;
                if (!event.altKey || event.ctrlKey || event.metaKey) return;

                const target = event.target;
                const inEditable = target && (
                    target.matches('input, textarea, select') ||
                    target.closest('input, textarea, select')
                );
                if (inEditable && target !== summaryInput) {
                    return;
                }

                if (event.key === '1') {
                    event.preventDefault();
                    applyPreset('today_afternoon');
                }
                if (event.key === '2') {
                    event.preventDefault();
                    applyPreset('tomorrow_morning');
                }
                if (event.key === '3') {
                    event.preventDefault();
                    applyPreset('tomorrow_afternoon');
                }
            });
        });
    </script>

    @if ($isActiveNoteOnlyFinish)
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 p-3 backdrop-blur sm:hidden">
            <form method="POST" action="{{ route('calls.end', $call) }}" class="js-active-call-end-form">
                @csrf
                @if ($isCallerMode)
                    <input type="hidden" name="caller_mode" value="1">
                @endif
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-4 text-base font-semibold text-white shadow-sm hover:bg-rose-700">
                    Ukoncit hovor
                </button>
            </form>
            <div class="mt-2 text-center text-[11px] text-slate-500">Tip: <kbd class="rounded bg-slate-100 px-1 py-0.5">Ctrl+Enter</kbd> v poznamce</div>
        </div>
    @endif
@endsection
