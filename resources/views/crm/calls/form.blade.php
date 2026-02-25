@php
    $flowMode = $flowMode ?? ($call->exists ? 'edit' : 'create');
    $isFinishFlow = $flowMode === 'finish';
    $finalizeCall = ($finalizeCall ?? false) || ! $isFinishFlow || $call->outcome !== 'pending';
    $isActiveNoteOnlyFinish = $isFinishFlow && ! $finalizeCall && $call->outcome === 'pending';
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

    @if ($isFinishFlow)
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            <div class="font-medium">Hovor bezi / byl prave zahajen</div>
            <div class="mt-1">
                Firma: <span class="font-medium">{{ $company?->name ?? '-' }}</span> |
                Start: <span class="font-medium">{{ $call->called_at?->format('Y-m-d H:i:s') ?? '-' }}</span>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $call->exists ? route('calls.update', $call) : route('calls.store') }}" class="js-call-flow-form space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
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
                @if ($isActiveNoteOnlyFinish)
                    <div class="mt-1 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                        Rozpracovano (vysledek vyberes az pri ukonceni hovoru)
                    </div>
                    <input type="hidden" name="outcome" value="pending">
                @else
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
                @endif
                @error('outcome')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @if ($isFinishFlow)
            <div>
                <label for="summary" class="block text-sm font-medium text-slate-700">Poznamka / shrnuti hovoru</label>
                <textarea id="summary" name="summary" rows="6" class="mt-1 w-full rounded-md border-slate-300" autofocus>{{ old('summary', $call->summary) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Poznamku muzes psat prubezne behem hovoru. Dalsi kroky se jen prizpusobi podle vysledku.</p>
                @error('summary')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        @if ($isFinishFlow && $finalizeCall)
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
                <label for="company_status" class="block text-sm font-medium text-slate-700">Zmenit stav firmy</label>
                <select id="company_status" name="company_status" class="mt-1 w-full rounded-md border-slate-300">
                    <option value="">Beze zmeny (nebo auto follow-up)</option>
                    @foreach (($companyStatuses ?? []) as $status)
                        <option value="{{ $status }}" @selected(old('company_status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                @error('company_status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
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
                <button type="submit" class="rounded-md bg-violet-700 px-4 py-2 text-sm font-medium text-white">Ulozit poznamku behem hovoru</button>
                <a href="{{ route('calls.finish', ['call' => $call, 'finalize_call' => 1, 'caller_mode' => $isCallerMode ? 1 : null]) }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">
                    Ukoncit hovor a vybrat vysledek
                </a>
            @else
                <button type="submit" class="rounded-md {{ $isFinishFlow ? 'bg-emerald-600' : 'bg-slate-900' }} px-4 py-2 text-sm font-medium text-white">{{ $submitText }}</button>
            @endif
            @if ($isFinishFlow && ! $isCallerMode && ! $isActiveNoteOnlyFinish)
                <button type="submit" name="submit_action" value="save_next_company" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white">
                    Ukoncit a dalsi firma
                </button>
            @endif
            @if ($call->exists)
                <a href="{{ $isCallerMode ? route('caller-mode.index') : route('calls.show', $call) }}" class="text-sm text-slate-600 hover:text-slate-900">Zrusit</a>
            @else
                <a href="{{ route('calls.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Zrusit</a>
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
            const statusInput = form.querySelector('#company_status');
            const summaryInput = form.querySelector('#summary');
            const presetButtons = Array.from(form.querySelectorAll('.js-followup-preset'));
            const isFinishFlow = {{ $isFinishFlow ? 'true' : 'false' }};
            const finalizeCall = {{ $finalizeCall ? 'true' : 'false' }};
            const callId = {{ $call->exists ? (int) $call->id : 'null' }};
            const draftKey = callId ? ('call-finish-summary-draft:' + String(callId)) : null;
            let autosaveTimer = null;
            let statusAutoTouched = false;

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

                if (statusInput && !statusInput.value) {
                    statusInput.value = 'follow-up';
                    statusAutoTouched = true;
                }
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
                    if (statusInput && !statusInput.value) {
                        statusInput.value = 'follow-up';
                        statusAutoTouched = true;
                    }
                }

                if (outcome === 'meeting-booked') {
                    if (!hasValue(meetingInput)) {
                        setDateValue(meetingInput, nextBusinessTime(1, 10));
                    }
                    if (statusInput && !statusInput.value) {
                        statusInput.value = 'qualified';
                        statusAutoTouched = true;
                    }
                }

                if (outcome === 'not-interested' && statusInput && !statusInput.value) {
                    statusInput.value = 'lost';
                    statusAutoTouched = true;
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

            if (statusInput) {
                statusInput.addEventListener('change', function () {
                    statusAutoTouched = false;
                });
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
@endsection
