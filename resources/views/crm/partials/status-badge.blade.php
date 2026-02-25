@php
    $value = (string) ($value ?? '');
    $context = (string) ($context ?? 'generic');

    $palettes = [
        'company' => [
            'new' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'contacted' => 'bg-blue-100 text-blue-800 ring-blue-200',
            'follow-up' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'qualified' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'lost' => 'bg-rose-100 text-rose-800 ring-rose-200',
        ],
        'follow-up' => [
            'open' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'done' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'cancelled' => 'bg-slate-100 text-slate-700 ring-slate-200',
        ],
        'lead-transfer' => [
            'pending' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'accepted' => 'bg-blue-100 text-blue-800 ring-blue-200',
            'done' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'cancelled' => 'bg-slate-100 text-slate-700 ring-slate-200',
        ],
        'meeting' => [
            'planned' => 'bg-blue-100 text-blue-800 ring-blue-200',
            'confirmed' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'done' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'cancelled' => 'bg-rose-100 text-rose-800 ring-rose-200',
        ],
    ];
    $labelsByContext = [
        'company' => [
            'new' => 'nová',
            'contacted' => 'kontaktována',
            'follow-up' => 'follow-up',
            'qualified' => 'kvalifikována',
            'lost' => 'ztraceno',
        ],
        'follow-up' => [
            'open' => 'otevřený',
            'done' => 'hotovo',
            'cancelled' => 'zrušeno',
        ],
        'lead-transfer' => [
            'pending' => 'čeká',
            'accepted' => 'přijato',
            'done' => 'hotovo',
            'cancelled' => 'zrušeno',
        ],
        'meeting' => [
            'planned' => 'plánováno',
            'confirmed' => 'potvrzeno',
            'done' => 'hotovo',
            'cancelled' => 'zrušeno',
        ],
    ];

    $classes = $palettes[$context][$value] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
    $label = $labelsByContext[$context][$value] ?? $value;
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $classes }}">
    {{ $label }}
</span>
