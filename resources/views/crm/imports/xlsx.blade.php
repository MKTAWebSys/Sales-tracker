@extends('layouts.crm', ['title' => 'XLSX Import | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">XLSX Import (planned)</h1>
        <p class="text-sm text-slate-600">
            Skeleton for future bulk import. Parser implementation is intentionally postponed until Composer package install is available.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h2 class="text-lg font-semibold">Planned workflow</h2>
            <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-slate-700">
                @foreach ($steps as $step)
                    <li>{{ $step }}</li>
                @endforeach
            </ol>

            <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                Import is not active yet. Next implementation step will likely use `maatwebsite/excel` after `composer` is available in the environment.
            </div>

            <form class="mt-6 space-y-4 opacity-70">
                <div>
                    <label class="block text-sm font-medium text-slate-700">XLSX file</label>
                    <input type="file" accept=".xlsx" disabled class="mt-1 block w-full rounded-md border-slate-300 bg-slate-100">
                </div>
                <button type="button" disabled class="rounded-md bg-slate-300 px-4 py-2 text-sm font-medium text-slate-600">
                    Upload (disabled)
                </button>
            </form>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold">Expected columns</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                @foreach ($requiredColumns as $column)
                    <li><code>{{ $column }}</code></li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
