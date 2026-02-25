@php
    $flowMode = $flowMode ?? ($call->exists ? 'edit' : 'create');
    $isFinishFlow = $flowMode === 'finish';
    $isActiveCall = $call->outcome === 'pending' && ! $call->ended_at;
    $finalizeCall = ($finalizeCall ?? false) || ! $isFinishFlow || $call->ended_at || $call->outcome !== 'pending';
    $isActiveNoteOnlyFinish = $isFinishFlow && ! $finalizeCall && $isActiveCall;
    $isCallerMode = request()->boolean('caller_mode');
    $isCreateFlow = $flowMode === 'create';
    $titleText = $isFinishFlow ? 'Ukoncit hovor' : ($call->exists ? 'Upravit hovor' : 'Novy hovor');
    $submitText = $isFinishFlow ? 'Ukoncit a ulozit hovor' : 'Ulozit';
    $company = $call->company ?? $companies->firstWhere('id', $call->company_id);
    $finishOutcomeDefault = ($isFinishFlow && $finalizeCall && $call->outcome === 'pending')
        ? 'callback'
        : ($call->outcome ?: 'callback');
@endphp

@extends('layouts.crm', ['title' => $titleText . ' | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $titleText }}</h1>
        @if ($isFinishFlow)
            <p class="text-sm text-slate-600">
                @if ($isActiveNoteOnlyFinish)
                    Aktivni hovor: zatim zapisuj jen poznamku. Vysledek a dalsi kroky vyberes az pri ukonceni hovoru.
                @else
                    Dopln vysledek hovoru a zobrazime jen relevantni dalsi kroky.
                @endif
            </p>
        @else
            <p class="text-sm text-slate-600">Zaznam hovoru s poli pro navazujici kroky.</p>
            <p class="mt-1 text-xs text-slate-500">Pokud vyplnis follow-up / schuzku / predani, navazane zaznamy se po ulozeni vytvori automaticky.</p>
        @endif
    </div>

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

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="called_at" class="block text-sm font-medium text-slate-700">{{ $isFinishFlow ? 'Cas startu hovoru' : 'Datum a cas hovoru' }}</label>
                    <input id="called_at" name="called_at" type="datetime-local" required value="{{ old('called_at', optional($call->called_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300" @readonly($isFinishFlow)>
                    @error('called_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="outcome" class="block text-sm font-medium text-slate-700">Vysledek</label>
                    <select id="outcome" name="outcome" class="js-call-outcome mt-1 w-full rounded-md border-slate-300">
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
                    @error('outcome')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        @if ($isFinishFlow)
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
            @php
                $startAt = old('called_at') ? \Illuminate\Support\Carbon::parse(old('called_at')) : $call->called_at;
                $endAt = old('ended_at') ? \Illuminate\Support\Carbon::parse(old('ended_at')) : $call->ended_at;
                $durationMinutes = ($startAt && $endAt && $endAt->greaterThanOrEqualTo($startAt))
                    ? $startAt->diffInMinutes($endAt)
                    : null;
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Cas hovoru</h2>
                <div class="mt-2 grid gap-3 sm:grid-cols-3 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                        <div class="text-xs text-slate-500">Od</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $startAt?->format('Y-m-d H:i:s') ?? '-' }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                        <div class="text-xs text-slate-500">Do</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $endAt?->format('Y-m-d H:i:s') ?? '-' }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                        <div class="text-xs text-slate-500">Pocet minut</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $durationMinutes !== null ? $durationMinutes : '-' }}</div>
                    </div>
                </div>
                @error('ended_at')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Dalsi krok po hovoru</h2>
                        <p class="mt-1 text-sm text-slate-600">Formular zobrazi jen pole, ktera typicky davas k vybranemu vysledku.</p>
                    </div>
                </div>

                <div class="js-callback-presets mt-4 hidden">
                    <div class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Rychly termin pro znovu zavolat</div>
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
                    </div>
                    <div class="mt-2 text-xs text-slate-500">
                        Zkratky: <kbd class="rounded bg-slate-200 px-1.5 py-0.5">Alt+1</kbd>,
                        <kbd class="rounded bg-slate-200 px-1.5 py-0.5">Alt+2</kbd>,
                        <kbd class="rounded bg-slate-200 px-1.5 py-0.5">Alt+3</kbd>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 sm:grid-cols-2 {{ $isActiveNoteOnlyFinish ? 'hidden' : '' }}">
            <div class="js-call-panel js-panel-followup" data-show-for="callback,no-answer,interested">
                <label for="next_follow_up_at" class="block text-sm font-medium text-slate-700">Dalsi follow-up</label>
                <input id="next_follow_up_at" name="next_follow_up_at" type="datetime-local" value="{{ old('next_follow_up_at', optional($call->next_follow_up_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                <p class="mt-1 text-xs text-slate-500">Po vyplneni se firma automaticky prepne do stavu follow-up (pokud nezvolis jiny stav niz).</p>
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

        <div class="grid gap-6 sm:grid-cols-2 {{ $isActiveNoteOnlyFinish ? 'hidden' : '' }}">
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
            <div class="js-call-panel js-panel-status" data-show-for="callback,no-answer,interested,not-interested,meeting-booked">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="text-sm font-medium text-slate-700">Stav firmy se zmeni automaticky podle vysledku hovoru.</div>
                    <p class="mt-1 text-xs text-slate-500">Napr. callback -> follow-up, schuzka -> qualified, bez zajmu -> lost.</p>
                </div>
            </div>
        </div>

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
                    Ukoncit a dalsi firma
                </button>
            @endif
            @if ($call->exists && ! $isActiveNoteOnlyFinish)
                <a href="{{ $isCallerMode ? route('caller-mode.index') : route('calls.show', $call) }}" class="text-sm text-slate-600 hover:text-slate-900">Zrusit</a>
            @else
                @unless ($isActiveNoteOnlyFinish)
                    <a href="{{ route('calls.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Zrusit</a>
                @endunless
            @endif
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.js-call-flow-form');
            if (!form) return;

            const outcomeSelect = form.querySelector('.js-call-outcome');
            const panels = Array.from(form.querySelectorAll('.js-call-panel'));
            const callbackPresetsWrap = form.querySelector('.js-callback-presets');
            const followUpInput = form.querySelector('#next_follow_up_at');
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
                }

                if (outcome === 'interested') {
                    if (!hasValue(followUpInput)) {
                        setDateValue(followUpInput, nextBusinessTime(2, 10));
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
                }

                applySmartDefaults(outcome);
            };

            if (outcomeSelect) {
                outcomeSelect.addEventListener('change', updatePanels);
                updatePanels();
            }

            presetButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    applyPreset(String(button.getAttribute('data-preset') || ''));
                });
            });

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
