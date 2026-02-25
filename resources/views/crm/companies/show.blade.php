@extends('layouts.crm', ['title' => $company->name . ' | Call CRM'])

@section('content')
    @php
        $isManager = auth()->user()?->isManager() ?? false;
        $canQueueClaim = $company->status === 'new' && $company->first_contacted_at === null && $company->first_caller_user_id === null;
        $canQueueUnassign = $company->status === 'new' && $company->first_contacted_at === null && (
            $isManager || (int) $company->first_caller_user_id === (int) auth()->id()
        );
    @endphp

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ $company->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">
                Stav: @include('crm.partials.status-badge', ['context' => 'company', 'value' => $company->status'])
                | Owner: {{ $company->assignedUser?->name ?? '-' }}
                | First caller: {{ $company->firstCaller?->name ?? '-' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('companies.calls.start', $company) }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Zahajit hovor</a>
            <a href="{{ route('companies.quick-defer', $company) }}" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white">Odlozit + dalsi firma</a>
            <a href="{{ route('imports.xlsx') }}" class="rounded-md bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200">Import XLSX</a>
            <a href="{{ route('calls.create', ['company_id' => $company->id]) }}" class="rounded-md bg-emerald-100 px-4 py-2 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">Novy hovor (rucne)</a>
            <a href="{{ route('companies.edit', $company) }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Upravit</a>
            <a href="{{ route('companies.index') }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700">Zpet</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h2 class="text-lg font-semibold">Detail firmy</h2>
            @php
                $icoDigits = $company->ico ? preg_replace('/\D+/', '', $company->ico) : null;
                $kurzyIcoUrl = $icoDigits ? "https://www.kurzy.cz/ico/{$icoDigits}/" : null;
            @endphp

            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                @if ($company->website)
                    <a href="{{ $company->website }}" target="_blank" rel="noreferrer" class="rounded-md bg-slate-100 px-3 py-1.5 text-slate-700 ring-1 ring-slate-200 hover:bg-slate-200">Otevrit web</a>
                @endif
                @if ($company->phone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $company->phone) }}" class="rounded-md bg-emerald-50 px-3 py-1.5 text-emerald-800 ring-1 ring-emerald-200 hover:bg-emerald-100">
                        Volat {{ $company->phone }}
                    </a>
                @endif
                @if ($kurzyIcoUrl)
                    <a href="{{ $kurzyIcoUrl }}" target="_blank" rel="noreferrer" class="rounded-md bg-blue-50 px-3 py-1.5 text-blue-800 ring-1 ring-blue-200 hover:bg-blue-100">Kurzy.cz podle ICO</a>
                @endif
            </div>

            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">Nazev</dt>
                    <dd class="font-medium">{{ $company->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">ICO</dt>
                    <dd class="font-medium">{{ $company->ico ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Web</dt>
                    <dd class="font-medium">
                        @if ($company->website)
                            <a href="{{ $company->website }}" target="_blank" rel="noreferrer" class="text-slate-700 underline">{{ $company->website }}</a>
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Kontaktni osoba</dt>
                    <dd class="font-medium">{{ $company->contact_person ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Telefon</dt>
                    <dd class="font-medium">{{ $company->phone ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Vytvoreno</dt>
                    <dd class="font-medium">{{ $company->created_at?->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Owner</dt>
                    <dd class="font-medium">{{ $company->assignedUser?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">First caller</dt>
                    <dd class="font-medium">{{ $company->firstCaller?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Queue assigned at</dt>
                    <dd class="font-medium">{{ $company->first_caller_assigned_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">First contacted at</dt>
                    <dd class="font-medium">{{ $company->first_contacted_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                </div>
            </dl>

            <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Call queue (first contact)</h3>
                        <p class="mt-1 text-sm text-slate-600">Fronta pro prvni osloveni je oddelena od ownera firmy.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($canQueueClaim)
                            <form method="POST" action="{{ route('companies.bulk') }}">
                                @csrf
                                <input type="hidden" name="bulk_action" value="claim_first_caller">
                                <input type="hidden" name="company_ids[]" value="{{ $company->id }}">
                                <button type="submit" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white">Vzit si do fronty</button>
                            </form>
                        @endif

                        @if ($canQueueUnassign && $company->first_caller_user_id)
                            <form method="POST" action="{{ route('companies.bulk') }}">
                                @csrf
                                <input type="hidden" name="bulk_action" value="unassign_first_caller">
                                <input type="hidden" name="company_ids[]" value="{{ $company->id }}">
                                <button type="submit" class="rounded-md bg-slate-200 px-3 py-2 text-sm font-medium text-slate-700">Odebrat z fronty</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Poznamky</h3>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $company->notes ?: 'Bez poznamek.' }}</p>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Timeline aktivit</h2>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('calls.index') }}" class="text-slate-600 hover:text-slate-900">Hovory</a>
                    <a href="{{ route('follow-ups.index') }}" class="text-slate-600 hover:text-slate-900">Follow-upy</a>
                </div>
            </div>
            <ul class="mt-4 space-y-3 text-sm text-slate-700">
                @forelse ($timeline as $index => $item)
                    @php
                        $isLatest = $index === 0;
                        $hasLongSummary = !empty($item['summary']) && mb_strlen(trim((string) $item['summary'])) > 180;
                    @endphp
                    <li class="js-timeline-item rounded-lg border p-3 transition {{ $isLatest ? 'border-emerald-200 bg-emerald-50/40 shadow-sm ring-1 ring-emerald-100' : 'border-slate-100 bg-white' }} {{ $hasLongSummary ? 'cursor-pointer' : '' }}" data-expanded="0" data-collapsed-class="{{ $isLatest ? 'line-clamp-6' : 'line-clamp-3' }}">
                        @if ($isLatest)
                            <div class="mb-2 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-800 ring-1 ring-emerald-200">Posledni aktivita</div>
                        @endif
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                @php
                                    $typeBadge = match ($item['type']) {
                                        'call' => 'bg-slate-100 text-slate-700 ring-slate-200',
                                        'follow-up' => 'bg-amber-100 text-amber-800 ring-amber-200',
                                        'lead-transfer' => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
                                        'meeting' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                                        default => 'bg-slate-100 text-slate-700 ring-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $typeBadge }}">{{ $item['type'] }}</span>

                                @if ($item['type'] === 'call')
                                    @include('crm.partials.call-outcome-badge', ['outcome' => $item['call']->outcome])
                                @else
                                    <div class="font-medium">{{ $item['title'] }}</div>
                                @endif
                            </div>
                            <a href="{{ $item['url'] }}" class="text-xs text-slate-600 hover:text-slate-900">Detail</a>
                        </div>
                        <div class="mt-1 text-slate-500">{{ $item['at']?->format('Y-m-d H:i') ?: '-' }}</div>
                        @if (!empty($item['meta']))
                            <div class="mt-1 text-xs text-slate-500">{{ $item['meta'] }}</div>
                        @endif
                        @if (!empty($item['summary']))
                            <p class="js-timeline-summary mt-2 text-slate-700 {{ $isLatest ? 'line-clamp-6' : 'line-clamp-3' }}">{{ $item['summary'] }}</p>
                            @if ($hasLongSummary)
                                <div class="mt-2">
                                    <button type="button" class="js-timeline-toggle text-xs font-medium text-slate-600 underline hover:text-slate-900">Rozbalit poznamku</button>
                                </div>
                            @endif
                        @endif
                    </li>
                @empty
                    <li class="text-slate-500">Zatim zadne aktivity.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            const toggleButton = event.target.closest('.js-timeline-toggle');
            const timelineItem = event.target.closest('.js-timeline-item');
            const clickedInteractive = event.target.closest('a, button, input, select, textarea, label');

            if (toggleButton) {
                event.preventDefault();
                const item = toggleButton.closest('.js-timeline-item');
                if (!item) return;
                const summary = item.querySelector('.js-timeline-summary');
                if (!summary) return;
                const collapsedClass = item.getAttribute('data-collapsed-class') || 'line-clamp-3';

                const expanded = item.getAttribute('data-expanded') === '1';
                item.setAttribute('data-expanded', expanded ? '0' : '1');
                summary.classList.remove('line-clamp-3', 'line-clamp-6');
                if (expanded) {
                    summary.classList.add(collapsedClass);
                }
                toggleButton.textContent = expanded ? 'Rozbalit poznamku' : 'Sbalit poznamku';
                return;
            }

            if (!timelineItem || clickedInteractive) return;

            const summary = timelineItem.querySelector('.js-timeline-summary');
            const action = timelineItem.querySelector('.js-timeline-toggle');
            if (!summary || !action) return;

            action.click();
        });
    </script>
@endsection
