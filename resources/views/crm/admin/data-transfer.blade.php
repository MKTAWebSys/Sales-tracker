@extends('layouts.crm', ['title' => 'Zaloha dat | Call CRM'])

@section('content')
    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Export snapshotu</h2>
            <p class="mt-1 text-sm text-slate-600">
                Stahne kompletni stav CRM (uzivatele, firmy, hovory, follow-upy, schuzky, predani leadu) do jednoho JSON souboru.
            </p>

            <div class="mt-4">
                <a href="{{ route('admin.data-transfer.export') }}"
                   class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Stahnout snapshot
                </a>
            </div>
        </section>

        <section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Import snapshotu</h2>
            <p class="mt-1 text-sm text-slate-600">
                Nahraje predchozi snapshot a prepise aktualni data CRM. Pouzij jen overeny soubor z tohoto systemu.
            </p>

            <form method="POST" action="{{ route('admin.data-transfer.import') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf

                <div>
                    <label for="snapshot" class="block text-sm font-medium text-slate-700">JSON soubor</label>
                    <input id="snapshot" name="snapshot" type="file" accept=".json,application/json,text/json,text/plain" required class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    @error('snapshot')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="inline-flex items-center rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-500">
                    Importovat a prepsat data
                </button>
            </form>
        </section>
    </div>

    <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
        Pro bezpecnost testuj importy nejdriv na kopii dat. Snapshot import prepise cele CRM tabulky.
    </div>
@endsection
