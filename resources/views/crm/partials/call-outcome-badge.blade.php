@php
    $outcome = (string) ($outcome ?? '');
    $map = [
        'pending' => 'bg-violet-100 text-violet-800 ring-violet-200',
        'meeting-booked' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'interested' => 'bg-blue-100 text-blue-800 ring-blue-200',
        'callback' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'no-answer' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'not-interested' => 'bg-rose-100 text-rose-800 ring-rose-200',
    ];
    $labels = [
        'pending' => 'rozpracovano',
        'meeting-booked' => 'schuzka domluvena',
        'interested' => 'zajem',
        'callback' => 'zavolat znovu',
        'no-answer' => 'nezastizen',
        'not-interested' => 'bez zajmu',
    ];
    $classes = $map[$outcome] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $classes }}">
    {{ $labels[$outcome] ?? $outcome }}
</span>
