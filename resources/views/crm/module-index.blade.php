@extends('layouts.crm', ['title' => $title . ' | Call CRM'])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">{{ $title }}</h1>
        <p class="text-sm text-slate-600">{{ $description }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <div class="border-b border-slate-100 px-4 py-3 text-sm font-medium">Poslední záznamy (placeholder)</div>
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        @foreach ($columns as $key => $label)
                            <th class="px-4 py-3">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <tr>
                            @foreach (array_keys($columns) as $key)
                                <td class="px-4 py-3">
                                    @php $value = data_get($row, $key); @endphp
                                    @if ($value instanceof \Carbon\CarbonInterface)
                                        {{ $value->format('d.m.Y H:i') }}
                                    @else
                                        {{ $value ?: '—' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="px-4 py-10 text-center text-slate-500">Zatím bez dat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold">MVP TODO</h2>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                @foreach ($todo as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
