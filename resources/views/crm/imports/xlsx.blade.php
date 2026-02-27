@extends('layouts.crm', ['title' => 'XLSX Import | Call CRM'])

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h2 class="text-base font-semibold text-slate-900">Import firem z XLSX</h2>
            <p class="mt-1 text-sm text-slate-600">
                Nahraj soubor, zkontroluj nahled a potvrd import. Duplicity lze preskocit nebo aktualizovat.
            </p>

            @if (session('status'))
                <div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-900">
                    <div class="font-semibold">Import se nepodaril:</div>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('imports.xlsx.preview') }}" enctype="multipart/form-data" class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">
                @csrf
                <div class="grid gap-2 sm:grid-cols-[1fr_120px]">
                    <input type="file" name="xlsx_file" accept=".xlsx" required class="block w-full rounded-md border-slate-300 text-sm">
                    <input type="number" name="row_limit" min="1" max="1000" value="{{ (int) ($rowLimit ?? 100) }}" class="rounded-md border-slate-300 text-sm" title="Pocet radku na test">
                </div>
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Nahrat a nahled</button>
            </form>
            <p class="mt-2 text-xs text-slate-500">Tip: pro test dej `100` radku.</p>

            @if ($previewSummary)
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                    Radky: {{ $previewSummary['total_rows'] }} |
                    nove: {{ $previewSummary['new_rows'] }} |
                    duplicity: {{ $previewSummary['duplicate_rows'] }} |
                    nevalidni: {{ $previewSummary['invalid_rows'] }}
                </div>

                <form method="POST" action="{{ route('imports.xlsx.confirm') }}" class="mt-3 flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="hidden" name="preview_token" value="{{ $previewToken }}">
                    <label for="duplicate_mode" class="text-sm font-medium text-slate-700">Duplicity:</label>
                    <select id="duplicate_mode" name="duplicate_mode" class="rounded-md border-slate-300 py-1.5 text-sm">
                        <option value="skip">Preskocit existujici firmy</option>
                        <option value="update">Aktualizovat existujici firmy</option>
                    </select>
                    <select name="default_assigned_user_id" class="rounded-md border-slate-300 py-1.5 text-sm" title="Vychozi aktivni resitel">
                        <option value="">Resitel z importu / bez zmeny</option>
                        @foreach (($users ?? collect()) as $userOption)
                            <option value="{{ $userOption->id }}" @selected((string) ($defaultAssignedUserId ?? '') === (string) $userOption->id)>
                                Resitel: {{ $userOption->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="default_first_caller_user_id" class="rounded-md border-slate-300 py-1.5 text-sm" title="Vychozi first caller">
                        <option value="">First caller z importu / bez zmeny</option>
                        @foreach (($users ?? collect()) as $userOption)
                            <option value="{{ $userOption->id }}" @selected((string) ($defaultFirstCallerUserId ?? '') === (string) $userOption->id)>
                                First caller: {{ $userOption->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                        Potvrdit import
                    </button>

                    @if (!empty($availableHeaders) && !empty($mappingFields))
                        <div class="mt-3 w-full rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="mb-2 text-sm font-medium text-slate-700">Rucni parovani sloupcu (volitelne)</div>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($mappingFields as $fieldKey => $fieldLabel)
                                    <label class="block text-xs text-slate-600">
                                        <span class="mb-1 block">{{ $fieldLabel }}</span>
                                        <select name="mapping[{{ $fieldKey }}]" class="w-full rounded-md border-slate-300 py-1.5 text-xs">
                                            <option value="">Auto</option>
                                            @foreach ($availableHeaders as $headerKey)
                                                <option value="{{ $headerKey }}" @selected((string) ($mappingDefaults[$fieldKey] ?? '') === (string) $headerKey)>{{ $headerKey }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </form>

                <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Radek</th>
                            <th class="px-3 py-2">Firma</th>
                            <th class="px-3 py-2">ICO</th>
                            <th class="px-3 py-2">Stav</th>
                            <th class="px-3 py-2">Prirazeni</th>
                            <th class="px-3 py-2">Detekce</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach (collect($previewRows)->take(80) as $row)
                            @php
                                $stateClass = $row['valid']
                                    ? ($row['duplicate'] ? 'text-amber-700' : 'text-emerald-700')
                                    : 'text-rose-700';
                                $stateText = $row['valid']
                                    ? ($row['duplicate'] ? 'duplicita' : 'novy')
                                    : 'nevalidni';
                            @endphp
                            <tr>
                                <td class="px-3 py-2 text-slate-500">{{ $row['row_number'] }}</td>
                                <td class="px-3 py-2 font-medium text-slate-900">{{ $row['company_name'] ?: '-' }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['ico'] ?: '-' }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['status'] ?: 'new' }}</td>
                                <td class="px-3 py-2 text-slate-600">
                                    {{ $row['assigned_user_email'] ?: '-' }}
                                    @if (!empty($row['first_caller_email']))
                                        / {{ $row['first_caller_email'] }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <span class="{{ $stateClass }}">{{ $stateText }}</span>
                                    @if (!empty($row['duplicate_label']))
                                        <div class="text-[11px] text-slate-500">{{ $row['duplicate_label'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($previewRows) > 80)
                    <p class="mt-2 text-xs text-slate-500">Zobrazeno prvnich 80 radku z nahledu.</p>
                @endif
            @endif
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Mapovani sloupcu</h2>
            <p class="mt-1 text-sm text-slate-600">Povinny je jen sloupec:</p>
            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                @foreach ($requiredColumns as $column)
                    <li><code>{{ $column }}</code></li>
                @endforeach
            </ul>

            <p class="mt-3 text-sm text-slate-600">Volitelne sloupce:</p>
            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                @foreach ($optionalColumns as $column)
                    <li><code>{{ $column }}</code></li>
                @endforeach
            </ul>

            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                Tip: hlavicka muze byt i cesky (napr. <code>Nazev subjektu</code>, <code>WWW</code>, <code>E-mail</code>, <code>ICO</code>, <code>Kdo oslovil</code>).
            </div>

            @if ($importReport)
                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-900">
                    <div>Zpracovano: {{ $importReport['processed'] ?? 0 }}</div>
                    <div>Vytvoreno: {{ $importReport['created'] ?? 0 }}</div>
                    <div>Aktualizovano: {{ $importReport['updated'] ?? 0 }}</div>
                    <div>Preskoceno duplicity: {{ $importReport['skipped_duplicate'] ?? 0 }}</div>
                    <div>Preskoceno nevalidni: {{ $importReport['skipped_invalid'] ?? 0 }}</div>
                    @if (!empty($importReport['log_file']))
                        <div class="mt-1 text-slate-700">Log: <code>{{ $importReport['log_file'] }}</code></div>
                    @endif
                    @if (!empty($importReport['errors']))
                        <div class="mt-2 font-semibold">Chyby:</div>
                        <ul class="mt-1 list-disc pl-4">
                            @foreach (collect($importReport['errors'])->take(8) as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
