@extends('layouts.crm', ['title' => 'Caller Mode | Call CRM'])

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold">Caller mode</h1>
                    <p class="text-sm text-slate-600">Mobilni workflow: dalsi firma -> volat -> ukoncit hovor.</p>
                </div>
                <a href="{{ route('companies.queue.mine') }}" class="rounded-md bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-200">Moje fronta</a>
            </div>
        </div>

        @if ($company)
            @php
                $cleanPhone = $company->phone ? preg_replace('/[^\d\+]/', '', $company->phone) : null;
            @endphp

            <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm ring-1 ring-emerald-100">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Moje dalsi firma</div>
                        <h2 class="mt-1 text-xl font-semibold">{{ $company->name }}</h2>
                    </div>
                    <a href="{{ route('companies.show', $company) }}" class="text-sm text-slate-600 underline">Detail</a>
                </div>

                @if ($activeCall)
                    <div class="mb-4 rounded-xl border border-violet-200 bg-violet-50/70 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-violet-700">Aktivni hovor</div>
                        <div class="mt-2 text-3xl font-semibold text-violet-900">
                            <span class="js-caller-mode-call-timer" data-called-at="{{ $activeCall->called_at?->toIso8601String() ?? '' }}">00:00</span>
                        </div>
                        <div class="mt-1 text-xs text-violet-700">
                            Start {{ $activeCall->called_at?->format('Y-m-d H:i:s') ?? '-' }}
                        </div>
                    </div>
                @endif

                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Kontaktni osoba</dt>
                        <dd class="font-medium">{{ $company->contact_person ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Telefon</dt>
                        <dd class="font-medium">{{ $company->phone ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Queue assigned</dt>
                        <dd class="font-medium">{{ $company->first_caller_assigned_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Owner</dt>
                        <dd class="font-medium">{{ $company->assignedUser?->name ?? '-' }}</dd>
                    </div>
                </dl>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @if ($activeCall)
                        <form method="POST" action="{{ route('calls.end', $activeCall) }}" class="contents">
                            @csrf
                            <input type="hidden" name="caller_mode" value="1">
                            <button type="submit" class="flex w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-4 text-base font-semibold text-white shadow-sm hover:bg-rose-700">
                                Ukoncit aktivni hovor
                            </button>
                        </form>
                    @else
                        <a href="{{ route('companies.calls.start', ['company' => $company, 'caller_mode' => 1]) }}" class="flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-4 text-base font-semibold text-white shadow-sm">
                            Zahajit hovor
                        </a>
                    @endif

                    @if ($cleanPhone)
                        <a href="tel:{{ $cleanPhone }}" class="flex items-center justify-center rounded-xl bg-slate-900 px-4 py-4 text-base font-semibold text-white shadow-sm">
                            Volat {{ $company->phone }}
                        </a>
                    @else
                        <a href="{{ route('companies.edit', $company) }}" class="flex items-center justify-center rounded-xl bg-amber-100 px-4 py-4 text-base font-semibold text-amber-900 ring-1 ring-amber-200">
                            Doplni telefon
                        </a>
                    @endif
                </div>

                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <a href="{{ route('companies.quick-defer', $company) }}" class="rounded-lg bg-amber-50 px-3 py-2 text-center text-sm font-medium text-amber-900 ring-1 ring-amber-200">
                        Odlozit + dalsi firma
                    </a>
                    <a href="{{ route('companies.next-mine', ['current_company_id' => $company->id, 'skip_lost' => 1]) }}" class="rounded-lg bg-slate-100 px-3 py-2 text-center text-sm font-medium text-slate-700 ring-1 ring-slate-200">
                        Preskocit na dalsi
                    </a>
                </div>
            </div>
        @else
            <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold">Fronta je prazdna</h2>
                <p class="mt-2 text-sm text-slate-600">Nemate zadnou firmu ve fronte prvniho osloveni.</p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    <a href="{{ route('companies.queue.mine') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Moje fronta</a>
                    <a href="{{ route('calendar.index') }}" class="rounded-md bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200">Kalendar</a>
                </div>
            </div>
        @endif

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Moje nejblizsi follow-upy</h2>
                <a href="{{ route('calendar.index') }}" class="text-sm text-slate-600 underline">Otevrit kalendar</a>
            </div>
            <ul class="space-y-2 text-sm">
                @forelse ($upcomingFollowUps as $followUp)
                    <li class="rounded-lg border border-slate-200 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-medium">{{ $followUp->company?->name ?? '-' }}</div>
                            <a href="{{ route('follow-ups.show', $followUp) }}" class="text-xs text-slate-600 underline">Detail</a>
                        </div>
                        <div class="mt-1 text-slate-500">{{ $followUp->due_at?->format('Y-m-d H:i') ?? '-' }}</div>
                    </li>
                @empty
                    <li class="text-slate-500">Zadne otevrene follow-upy.</li>
                @endforelse
            </ul>
        </div>
    </div>

    @if ($activeCall)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const timerEl = document.querySelector('.js-caller-mode-call-timer');
                if (!timerEl) return;

                const calledAtIso = String(timerEl.getAttribute('data-called-at') || '');
                if (!calledAtIso) return;

                const startedAt = new Date(calledAtIso);
                const formatDuration = function (totalSeconds) {
                    const seconds = Math.max(0, totalSeconds | 0);
                    const hrs = Math.floor(seconds / 3600);
                    const mins = Math.floor((seconds % 3600) / 60);
                    const secs = seconds % 60;
                    const pad = (n) => String(n).padStart(2, '0');
                    return hrs > 0 ? (pad(hrs) + ':' + pad(mins) + ':' + pad(secs)) : (pad(mins) + ':' + pad(secs));
                };

                const tick = function () {
                    const now = new Date();
                    const diffSeconds = Math.floor((now.getTime() - startedAt.getTime()) / 1000);
                    timerEl.textContent = formatDuration(diffSeconds);
                };

                tick();
                window.setInterval(tick, 1000);
            });
        </script>
    @endif
@endsection
