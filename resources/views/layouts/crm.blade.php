<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Call CRM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    @php
        $currentCompanyParam = request()->route('company');
        $currentCompanyId = is_object($currentCompanyParam) ? ($currentCompanyParam->id ?? null) : (is_numeric($currentCompanyParam) ? (int) $currentCompanyParam : null);

        $navSections = [
            [
                'title' => 'Prace',
                'links' => [
                    ['Dashboard', 'dashboard'],
                    ['Caller mode', 'caller-mode.index'],
                    ['Moje fronta', 'companies.queue.mine'],
                    ['Firmy', 'companies.index'],
                ],
            ],
            [
                'title' => 'Aktivity',
                'links' => [
                    ['Kalendar', 'calendar.index'],
                    ['Hovory', 'calls.index'],
                    ['Follow-upy', 'follow-ups.index'],
                    ['Predani leadu', 'lead-transfers.index'],
                    ['Schuzky', 'meetings.index'],
                ],
            ],
        ];

        if (auth()->check() && auth()->user()?->isManager()) {
            $navSections[0]['links'] = array_values(array_filter(
                $navSections[0]['links'],
                fn (array $link) => $link[1] !== 'caller-mode.index'
            ));
        }

        if (auth()->check() && auth()->user()?->isManager()) {
            $navSections[] = [
                'title' => 'Sprava',
                'links' => [
                    ['Uzivatele', 'users.index'],
                ],
            ];
        }
    @endphp

    @php
        $activeCallBanner = null;
        if (auth()->check()) {
            $activeCallBanner = \App\Models\Call::query()
                ->with('company:id,name')
                ->where('caller_id', auth()->id())
                ->where('outcome', 'pending')
                ->whereNull('ended_at')
                ->latest('called_at')
                ->first();
        }
    @endphp

    <div class="min-h-screen lg:grid lg:grid-cols-[18rem_1fr]">
        <aside class="border-b border-slate-200 bg-white lg:min-h-screen lg:border-b-0 lg:border-r">
            <div class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur supports-[backdrop-filter]:bg-white/80 lg:px-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <a href="{{ route('home') }}" class="text-lg font-semibold">Call CRM</a>
                        <p class="text-xs text-slate-500">MVP evidence obchodnich callu</p>
                    </div>
                    @auth
                        <a href="{{ route('companies.next-mine', array_filter(['current_company_id' => $currentCompanyId, 'skip_lost' => 1])) }}"
                           class="rounded-md bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700">
                            Dalsi firma
                        </a>
                    @endauth
                </div>
            </div>

            <nav class="px-4 py-4 lg:px-5">
                <div class="space-y-5">
                    @foreach ($navSections as $section)
                        <div>
                            <div class="mb-2 px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $section['title'] }}</div>
                            <div class="space-y-1">
                                @foreach ($section['links'] as [$label, $routeName])
                                    <a href="{{ route($routeName) }}"
                                       class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs($routeName) ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </nav>
        </aside>

        <div class="min-w-0">
            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @yield('content')
            </main>
        </div>
    </div>

    @if ($activeCallBanner)
        <div class="fixed bottom-4 right-4 z-50 w-[min(42rem,calc(100vw-2rem))] rounded-xl border border-violet-200 bg-violet-50/95 p-4 text-sm text-violet-900 shadow-xl ring-1 ring-violet-100 backdrop-blur">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="font-semibold">Probiha hovor</div>
                    <div class="mt-1">
                        {{ $activeCallBanner->company?->name ?? 'Bez firmy' }}
                        | start {{ $activeCallBanner->called_at?->format('Y-m-d H:i:s') ?? '-' }}
                        | bezi
                        <span class="js-active-call-timer font-semibold" data-called-at="{{ $activeCallBanner->called_at?->toIso8601String() ?? '' }}">00:00</span>
                    </div>
                    <form method="POST" action="{{ route('calls.quick-note', $activeCallBanner) }}" class="js-active-call-quick-note-form mt-3 flex flex-col gap-2 sm:flex-row sm:items-start" data-row-link-ignore>
                        @csrf
                        <textarea
                            name="note"
                            rows="2"
                            data-row-link-ignore
                            class="w-full rounded-md border-violet-200 bg-white/90 text-sm text-slate-900 placeholder:text-slate-400 focus:border-violet-400 focus:ring-violet-400 sm:min-w-[22rem]"
                            placeholder="Rychla poznamka behem hovoru..."
                        ></textarea>
                        <div class="flex items-center gap-2">
                            <button type="submit" class="js-active-call-quick-note-submit rounded-md bg-violet-700 px-3 py-2 text-xs font-medium text-white sm:mt-0">
                                Ulozit poznamku
                            </button>
                            <span class="js-active-call-quick-note-status text-xs text-violet-700" aria-live="polite"></span>
                        </div>
                    </form>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('calls.finish', ['call' => $activeCallBanner, 'caller_mode' => request()->routeIs('caller-mode.*') ? 1 : null]) }}"
                       class="rounded-md bg-violet-700 px-3 py-2 text-xs font-medium text-white">
                        Zapsat poznamku / vratit se k hovoru
                    </a>
                    <form method="POST" action="{{ route('calls.end', $activeCallBanner) }}" data-row-link-ignore>
                        @csrf
                        @if (request()->routeIs('caller-mode.*'))
                            <input type="hidden" name="caller_mode" value="1">
                        @endif
                        <button type="submit" class="rounded-md bg-white px-3 py-2 text-xs font-medium text-violet-800 ring-1 ring-violet-200">
                            Ukoncit hovor
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if (session('status'))
        <div
            id="crm-status-toast"
            class="fixed bottom-4 left-4 z-50 max-w-md rounded-xl border border-emerald-200 bg-emerald-50/95 px-4 py-3 text-sm text-emerald-900 shadow-lg ring-1 ring-emerald-100 backdrop-blur"
            role="status"
            aria-live="polite"
        >
            <div class="flex items-start gap-3">
                <div class="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-emerald-500"></div>
                <div class="min-w-0 flex-1">{{ session('status') }}</div>
                <button type="button" class="shrink-0 text-emerald-700 hover:text-emerald-900" aria-label="Zavrit hlasku" data-toast-close>
                    x
                </button>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('click', function (event) {
            const target = event.target instanceof Element ? event.target : event.target?.parentElement;
            if (!target) return;

            const interactive = target.closest('[data-row-link-ignore], a, button, input, select, textarea, label, form');
            if (interactive) return;

            const row = target.closest('tr[data-row-link]');
            if (!row) return;

            const href = row.getAttribute('data-row-link');
            if (!href) return;

            window.location.href = href;
        });

        document.addEventListener('click', function (event) {
            const close = event.target.closest('[data-toast-close]');
            if (!close) return;
            const toast = close.closest('#crm-status-toast');
            if (toast) toast.remove();
        });

        document.addEventListener('change', function (event) {
            const select = event.target.closest('.js-inline-save-select');
            if (!select) return;

            const form = select.closest('.js-inline-save-form');
            const button = form ? form.querySelector('.js-inline-save-btn') : null;
            if (!button) return;

            const initial = String(select.getAttribute('data-initial-value') ?? '');
            const current = String(select.value ?? '');
            const changed = initial !== current;

            button.classList.toggle('invisible', !changed);
        });

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('.js-inline-save-form');
            if (!form) return;
            const button = form.querySelector('.js-inline-save-btn');
            if (!button) return;
            button.disabled = true;
            button.classList.remove('invisible');
            button.textContent = '...';
        });

        document.addEventListener('pointerdown', function (event) {
            const input = event.target.closest('input[type="date"], input[type="datetime-local"], input[type="time"]');
            if (!input) return;
            if (typeof input.showPicker !== 'function') return;

            window.setTimeout(function () {
                try {
                    input.showPicker();
                } catch (error) {
                }
            }, 0);
        });

        @if ($activeCallBanner)
            document.addEventListener('DOMContentLoaded', function () {
                const activeCallReturnUrl = @json(route('calls.finish', ['call' => $activeCallBanner]));
                const activeCompanyId = @json((int) $activeCallBanner->company_id);
                const activeCallTimer = document.querySelector('.js-active-call-timer');
                const calledAtIso = activeCallTimer ? String(activeCallTimer.getAttribute('data-called-at') || '') : '';

                const formatDuration = function (totalSeconds) {
                    const seconds = Math.max(0, totalSeconds | 0);
                    const hrs = Math.floor(seconds / 3600);
                    const mins = Math.floor((seconds % 3600) / 60);
                    const secs = seconds % 60;
                    const pad = (n) => String(n).padStart(2, '0');
                    return hrs > 0 ? (pad(hrs) + ':' + pad(mins) + ':' + pad(secs)) : (pad(mins) + ':' + pad(secs));
                };

                if (activeCallTimer && calledAtIso) {
                    const startedAt = new Date(calledAtIso);
                    const tick = function () {
                        const now = new Date();
                        const diffSeconds = Math.floor((now.getTime() - startedAt.getTime()) / 1000);
                        activeCallTimer.textContent = formatDuration(diffSeconds);
                    };
                    tick();
                    window.setInterval(tick, 1000);
                }

                const quickNoteForm = document.querySelector('.js-active-call-quick-note-form');
                if (quickNoteForm) {
                    const quickNoteTextarea = quickNoteForm.querySelector('textarea[name="note"]');
                    const quickNoteSubmit = quickNoteForm.querySelector('.js-active-call-quick-note-submit');
                    const quickNoteStatus = quickNoteForm.querySelector('.js-active-call-quick-note-status');
                    let quickNoteStatusTimer = null;

                    quickNoteForm.addEventListener('submit', async function (event) {
                        event.preventDefault();

                        const note = quickNoteTextarea ? String(quickNoteTextarea.value || '').trim() : '';
                        if (!note) {
                            if (quickNoteStatus) quickNoteStatus.textContent = 'Zadej poznamku';
                            return;
                        }

                        if (quickNoteSubmit) {
                            quickNoteSubmit.disabled = true;
                            quickNoteSubmit.textContent = 'Ukladam...';
                        }
                        if (quickNoteStatus) {
                            quickNoteStatus.textContent = 'Ukladam...';
                        }

                        const formData = new FormData(quickNoteForm);
                        const token = quickNoteForm.querySelector('input[name="_token"]')?.value || '';

                        try {
                            const response = await fetch(quickNoteForm.action, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                },
                                body: formData,
                                credentials: 'same-origin',
                            });

                            const payload = await response.json().catch(() => ({}));
                            if (!response.ok) {
                                throw new Error(payload.message || 'Ulozeni poznamky selhalo');
                            }

                            if (quickNoteTextarea) {
                                quickNoteTextarea.value = '';
                                quickNoteTextarea.focus();
                            }

                            if (quickNoteStatus) {
                                quickNoteStatus.textContent = 'Poznamka ulozena';
                                window.clearTimeout(quickNoteStatusTimer);
                                quickNoteStatusTimer = window.setTimeout(function () {
                                    quickNoteStatus.textContent = '';
                                }, 1800);
                            }
                        } catch (error) {
                            if (quickNoteStatus) {
                                quickNoteStatus.textContent = error instanceof Error ? error.message : 'Chyba ulozeni';
                            }
                        } finally {
                            if (quickNoteSubmit) {
                                quickNoteSubmit.disabled = false;
                                quickNoteSubmit.textContent = 'Ulozit poznamku';
                            }
                        }
                    });
                }

                document.querySelectorAll('a[href*="/start-call"]').forEach(function (link) {
                    const href = String(link.getAttribute('href') || '');
                    if (!href) return;
                    if (href.includes('/companies/' + activeCompanyId + '/start-call')) {
                        return;
                    }

                    link.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-auto');
                    link.setAttribute('aria-disabled', 'true');
                    link.setAttribute('title', 'Mate aktivni hovor. Nejdriv se vratte k nemu.');

                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        window.location.href = activeCallReturnUrl;
                    });
                });
            });
        @endif

        window.setTimeout(function () {
            const toast = document.getElementById('crm-status-toast');
            if (!toast) return;
            toast.style.transition = 'opacity 180ms ease, transform 180ms ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(6px)';
            window.setTimeout(function () {
                toast.remove();
            }, 200);
        }, 3200);
    </script>
</body>
</html>
