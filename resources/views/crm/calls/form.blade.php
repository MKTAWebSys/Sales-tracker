@php
    $flowMode = $flowMode ?? ($call->exists ? 'edit' : 'create');
    $isFinishFlow = $flowMode === 'finish';
    $isCreateFlow = $flowMode === 'create';
    $titleText = $isFinishFlow ? 'Ukoncit hovor' : ($call->exists ? 'Upravit hovor' : 'Novy hovor');
    $submitText = $isFinishFlow ? 'Ukoncit a ulozit hovor' : 'Ulozit';
    $company = $call->company ?? $companies->firstWhere('id', $call->company_id);
@endphp

@extends('layouts.crm', ['title' => $titleText . ' | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $titleText }}</h1>
        @if ($isFinishFlow)
            <p class="text-sm text-slate-600">Dopln vysledek hovoru, poznamku a navazujici kroky. Cas startu uz je zaevidovany.</p>
        @else
            <p class="text-sm text-slate-600">Zaznam hovoru s poli pro navazujici kroky.</p>
            <p class="mt-1 text-xs text-slate-500">
                Pokud vyplnis follow-up / schuzku / predani, navazane zaznamy se po ulozeni vytvori automaticky.
            </p>
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

    <form method="POST" action="{{ $call->exists ? route('calls.update', $call) : route('calls.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @if ($call->exists)
            @method('PUT')
        @endif
        <input type="hidden" name="flow_mode" value="{{ $flowMode }}">

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
                <select id="outcome" name="outcome" class="mt-1 w-full rounded-md border-slate-300">
                    @foreach (['pending', 'no-answer', 'callback', 'interested', 'not-interested', 'meeting-booked'] as $outcome)
                        <option value="{{ $outcome }}" @selected(old('outcome', $call->outcome ?: 'callback') === $outcome)>
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

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="next_follow_up_at" class="block text-sm font-medium text-slate-700">Dalsi follow-up</label>
                <input id="next_follow_up_at" name="next_follow_up_at" type="datetime-local" value="{{ old('next_follow_up_at', optional($call->next_follow_up_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                <p class="mt-1 text-xs text-slate-500">Po vyplneni se firma automaticky prepne do stavu follow-up (pokud nezvolis jiny stav niz).</p>
                @error('next_follow_up_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="meeting_planned_at" class="block text-sm font-medium text-slate-700">Planovana schuzka</label>
                <input id="meeting_planned_at" name="meeting_planned_at" type="datetime-local" value="{{ old('meeting_planned_at', optional($call->meeting_planned_at)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-md border-slate-300">
                @error('meeting_planned_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
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
            <div>
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

        <div>
            <label for="summary" class="block text-sm font-medium text-slate-700">Poznamka / shrnuti hovoru</label>
            <textarea id="summary" name="summary" rows="6" class="mt-1 w-full rounded-md border-slate-300" @if($isFinishFlow) autofocus @endif>{{ old('summary', $call->summary) }}</textarea>
            @error('summary')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="rounded-md {{ $isFinishFlow ? 'bg-emerald-600' : 'bg-slate-900' }} px-4 py-2 text-sm font-medium text-white">{{ $submitText }}</button>
            @if ($isFinishFlow)
                <button type="submit" name="submit_action" value="save_next_company" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white">
                    Ukoncit a dalsi firma
                </button>
            @endif
            @if ($call->exists)
                <a href="{{ route('calls.show', $call) }}" class="text-sm text-slate-600 hover:text-slate-900">Zrusit</a>
            @else
                <a href="{{ route('calls.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Zrusit</a>
            @endif
        </div>
    </form>
@endsection
