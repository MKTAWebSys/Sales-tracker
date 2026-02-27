<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Call CRM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            try {
                if (window.localStorage.getItem('crm.sidebar.collapsed') === '1') {
                    document.documentElement.classList.add('crm-sidebar-collapsed');
                }
            } catch (error) {
            }
        })();
    </script>
    <style>
        .crm-main main h1.text-2xl.font-semibold {
            display: none;
        }

        @media (min-width: 1024px) {
            .crm-shell {
                display: grid;
                grid-template-columns: 12rem minmax(0, 1fr);
                min-height: 100vh;
                transition: grid-template-columns 160ms ease;
            }

            .crm-sidebar,
            .crm-main {
                transition: none;
            }

            html.crm-sidebar-collapsed .crm-shell,
            .crm-shell.is-collapsed {
                grid-template-columns: 5rem minmax(0, 1fr);
            }

            html.crm-sidebar-collapsed .crm-shell .crm-brand-copy,
            html.crm-sidebar-collapsed .crm-shell .crm-next-company-label,
            .crm-shell.is-collapsed .crm-brand-copy,
            .crm-shell.is-collapsed .crm-next-company-label {
                display: none;
            }

            html.crm-sidebar-collapsed .crm-shell .crm-nav-section-title,
            .crm-shell.is-collapsed .crm-nav-section-title {
                visibility: hidden;
            }

            html.crm-sidebar-collapsed .crm-shell .crm-nav-label,
            .crm-shell.is-collapsed .crm-nav-label {
                opacity: 0;
                width: 0;
                overflow: hidden;
                white-space: nowrap;
                pointer-events: none;
            }

            .crm-nav-label {
                transition: opacity 120ms ease;
            }

            html.crm-sidebar-collapsed .crm-shell .crm-next-company-btn,
            .crm-shell.is-collapsed .crm-next-company-btn {
                width: 2.5rem;
                min-width: 2.5rem;
                height: 2.5rem;
                padding: 0;
            }

            html.crm-sidebar-collapsed .crm-shell .crm-next-company-btn,
            html.crm-sidebar-collapsed .crm-shell .crm-sidebar-footer-btn {
                justify-content: center;
                width: 2.5rem;
                min-width: 2.5rem;
                height: 2.5rem;
                padding: 0;
                gap: 0;
                margin-left: auto;
                margin-right: auto;
            }

            html.crm-sidebar-collapsed .crm-shell .crm-nav-link,
            .crm-shell.is-collapsed .crm-nav-link {
                width: 2.5rem;
                min-width: 2.5rem;
                height: 2.5rem;
                min-height: 2.5rem;
                padding: 0;
                gap: 0;
                justify-content: center;
                margin-left: auto;
                margin-right: auto;
            }

            html.crm-sidebar-collapsed .crm-shell .crm-sidebar-header,
            .crm-shell.is-collapsed .crm-sidebar-header {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            html.crm-sidebar-collapsed .crm-shell .crm-sidebar-brand-row,
            .crm-shell.is-collapsed .crm-sidebar-brand-row {
                justify-content: center;
            }

            html.crm-sidebar-collapsed .crm-shell .crm-brand-link,
            .crm-shell.is-collapsed .crm-brand-link {
                width: 2.5rem;
                min-width: 2.5rem;
                justify-content: center;
            }

            .crm-sidebar-header {
                min-height: 5.25rem;
            }

            .crm-nav-section-title {
                display: flex;
                align-items: center;
                min-height: 1.25rem;
                height: 1.25rem;
                line-height: 1.25rem;
                margin-bottom: 0.5rem;
            }

            .crm-nav-link,
            .crm-next-company-btn,
            .crm-sidebar-footer-btn {
                min-height: 2.5rem;
            }

            .crm-nav-icon {
                flex: 0 0 1rem;
            }

            .crm-sidebar-footer-btn .crm-nav-icon,
            .crm-next-company-btn .crm-nav-icon {
                margin: 0;
            }

            .crm-brand-copy {
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 0;
                height: auto;
            }

            .crm-brand-title {
                font-size: 12px;
                line-height: 12px;
            }

        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    @php
        $currentCompanyParam = request()->route('company');
        $currentCompanyId = is_object($currentCompanyParam) ? ($currentCompanyParam->id ?? null) : (is_numeric($currentCompanyParam) ? (int) $currentCompanyParam : null);

        $navSections = [
            [
                'title' => 'Prace',
                'links' => [
                    ['Dashboard', 'dashboard', 'dashboard'],
                    ['Caller mode', 'caller-mode.index', 'phone'],
                    ['Moje fronta', 'companies.queue.mine', 'queue'],
                    ['Firmy', 'companies.index', 'companies'],
                ],
            ],
            [
                'title' => 'Aktivity',
                'links' => [
                    ['Kalendar', 'calendar.index', 'calendar'],
                    ['Hovory', 'calls.index', 'calls'],
                    ['Schuzky', 'meetings.index', 'meetings'],
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
                    ['Uzivatele', 'users.index', 'users'],
                    ['Zaloha dat', 'admin.data-transfer.index', 'backup'],
                ],
            ];
        }
    @endphp

    @php
        $activeCallBanner = null;
        $hideActiveCallBanner = false;
        if (auth()->check()) {
            $activeCallBanner = \App\Models\Call::query()
                ->with('company:id,name')
                ->where('caller_id', auth()->id())
                ->where('outcome', 'pending')
                ->whereNull('ended_at')
                ->latest('called_at')
                ->first();

            if ($activeCallBanner) {
                $routeCallParam = request()->route('call');
                $routeCallId = is_object($routeCallParam) ? ($routeCallParam->id ?? null) : (is_numeric($routeCallParam) ? (int) $routeCallParam : null);

                $hideActiveCallBanner = $routeCallId === $activeCallBanner->id
                    && (request()->routeIs('calls.show') || request()->routeIs('calls.finish'));
            }
        }
    @endphp

    <div id="crm-shell" class="crm-shell min-h-screen lg:min-h-screen">
        <aside class="crm-sidebar border-b border-slate-200 bg-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col lg:overflow-y-auto lg:border-b-0 lg:border-r">
            <div class="crm-sidebar-header border-b border-slate-200 bg-white px-3 py-3 lg:px-4">
                <div class="crm-sidebar-brand-row flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('home') }}" class="crm-brand-link flex items-center gap-2 text-lg font-semibold">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-xs font-bold text-white">CRM</span>
                            <span class="crm-brand-copy min-w-0">
                                <span class="crm-brand-title block truncate whitespace-nowrap">Call CRM</span>
                            </span>
                        </a>
                    </div>
                </div>
                @auth
                    <div class="mt-3">
                        <a href="{{ route('companies.next-mine', array_filter(['current_company_id' => $currentCompanyId, 'skip_lost' => 1])) }}"
                           class="crm-next-company-btn inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700">
                            <span class="crm-nav-icon inline-flex h-4 w-4 items-center justify-center">
                                <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4.5 4.5h2.7l.9 2.5-1.7.9a9.8 9.8 0 0 0 4.7 4.7l.9-1.7 2.5.9v2.7a1.8 1.8 0 0 1-1.8 1.8A10.7 10.7 0 0 1 2.7 6.3 1.8 1.8 0 0 1 4.5 4.5Z" />
                                    <path d="M11.5 6.5h4" />
                                    <path d="m13.8 4.2 2.3 2.3-2.3 2.3" />
                                </svg>
                            </span>
                            <span class="crm-next-company-label">Dalsi firma</span>
                        </a>
                    </div>
                @endauth
            </div>

            <nav class="crm-sidebar-inner px-3 py-3 lg:flex lg:flex-1 lg:flex-col lg:px-4">
                <div class="crm-nav-sections space-y-5">
                    @foreach ($navSections as $section)
                        <div>
                            <div class="crm-nav-section-title px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $section['title'] }}</div>
                            <div class="space-y-1">
                                @foreach ($section['links'] as [$label, $routeName, $icon])
                                    <a href="{{ route($routeName) }}"
                                       title="{{ $label }}"
                                       class="crm-nav-link flex items-center gap-3 rounded-md px-3 py-2 text-sm {{ request()->routeIs($routeName) ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                                        <span class="crm-nav-icon inline-flex h-4 w-4 items-center justify-center">
                                            @switch($icon)
                                                @case('dashboard')
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8"><path d="M3 3h6v6H3z"/><path d="M11 3h6v4h-6z"/><path d="M11 9h6v8h-6z"/><path d="M3 11h6v6H3z"/></svg>
                                                    @break
                                                @case('phone')
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8"><path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/></svg>
                                                    @break
                                                @case('queue')
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8"><path d="M4 5h12"/><path d="M4 10h9"/><path d="M4 15h7"/><path d="m14 9 3 3-3 3"/></svg>
                                                    @break
                                                @case('companies')
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8"><path d="M3 17h14"/><path d="M5 17V6h4v11"/><path d="M11 17V3h4v14"/><path d="M6 8h2"/><path d="M12 5h2"/><path d="M12 8h2"/></svg>
                                                    @break
                                                @case('calendar')
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="14" height="13" rx="2"/><path d="M6 2v4"/><path d="M14 2v4"/><path d="M3 8h14"/></svg>
                                                    @break
                                                @case('calls')
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8"><path d="M5 3h3l1 3-2 1a12 12 0 0 0 6 6l1-2 3 1v3a2 2 0 0 1-2 2A12 12 0 0 1 3 5a2 2 0 0 1 2-2Z"/></svg>
                                                    @break
                                                @case('followups')
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8"><path d="M16 10a6 6 0 1 1-2-4.47"/><path d="M16 4v4h-4"/></svg>
                                                    @break
                                                @case('transfers')
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8"><path d="M4 7h9"/><path d="m10 4 3 3-3 3"/><path d="M16 13H7"/><path d="m10 10-3 3 3 3"/></svg>
                                                    @break
                                                @case('meetings')
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="14" height="13" rx="2"/><path d="M6 2v4"/><path d="M14 2v4"/><path d="M3 8h14"/><path d="M10 10v3"/><path d="M10 10h2"/></svg>
                                                    @break
                                                @case('users')
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8"><path d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M4 17a6 6 0 0 1 12 0"/></svg>
                                                    @break
                                                @case('backup')
                                                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.8"><path d="M4 6h12"/><path d="M4 10h12"/><path d="M4 14h8"/><path d="m12 14 2 2 3-3"/></svg>
                                                    @break
                                                @default
                                                    <span class="text-xs font-semibold">{{ strtoupper(substr($label, 0, 1)) }}</span>
                                            @endswitch
                                        </span>
                                        <span class="crm-nav-label">{{ $label }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 border-t border-slate-200 pt-4">
                    <button type="button" id="crm-sidebar-toggle" class="crm-sidebar-footer-btn hidden w-full items-center justify-center gap-3 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 lg:inline-flex" aria-label="Sbalit bocni listu" title="Sbalit bocni listu">
                        <span class="crm-nav-icon inline-flex h-4 w-4 items-center justify-center">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M13 4 7 10 13 16" class="crm-sidebar-toggle-chevron" />
                            </svg>
                        </span>
                        <span class="crm-nav-label">Sbalit menu</span>
                    </button>
                </div>
            </nav>
        </aside>

        <div class="crm-main min-w-0">
            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @yield('content')
            </main>
        </div>
    </div>

    @if ($activeCallBanner && ! $hideActiveCallBanner)
        <a href="{{ route('calls.finish', ['call' => $activeCallBanner, 'caller_mode' => request()->routeIs('caller-mode.*') ? 1 : null]) }}"
           class="fixed bottom-4 right-4 z-50 block w-[min(32rem,calc(100vw-1rem))] rounded-xl border border-slate-700/70 bg-gradient-to-b from-slate-950/95 via-slate-900/95 to-slate-800/95 p-3 text-sm text-white shadow-2xl ring-1 ring-white/10 backdrop-blur hover:from-slate-900/95 hover:to-slate-800/95 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300">
            <div class="grid grid-cols-[minmax(0,1fr)_auto] grid-rows-[auto_auto] items-stretch gap-2">
                <div class="col-start-1 row-start-1 min-w-0 flex items-center gap-2">
                        <span class="inline-flex h-2 w-2 shrink-0 rounded-full bg-emerald-400"></span>
                        <div class="min-w-0 truncate text-sm font-semibold leading-tight">
                            {{ $activeCallBanner->company?->name ?? 'Bez firmy' }}
                        </div>
                        <span class="ml-auto shrink-0 rounded-full bg-white/10 px-2 py-0.5 text-xs font-semibold text-white ring-1 ring-white/10">
                            <span class="js-active-call-timer" data-called-at="{{ $activeCallBanner->called_at?->toIso8601String() ?? '' }}">00:00</span>
                        </span>
                </div>
                <div class="col-start-1 row-start-2 min-w-0">
                    <details class="rounded-md border border-white/10 bg-white/5 p-2" data-row-link-ignore onclick="event.stopPropagation();">
                        <summary class="cursor-pointer select-none text-xs font-medium text-slate-200" onclick="event.preventDefault(); this.parentElement.open = !this.parentElement.open;">
                            Rychla poznamka / detail
                        </summary>
                        <form method="POST" action="{{ route('calls.quick-note', $activeCallBanner) }}" class="js-active-call-quick-note-form mt-2 flex flex-col gap-2 sm:flex-row sm:items-start" data-row-link-ignore>
                            @csrf
                            <textarea
                                name="note"
                                rows="2"
                                data-row-link-ignore
                                class="w-full rounded-md border-white/15 bg-white/95 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-300 focus:ring-emerald-300 sm:min-w-[18rem]"
                                placeholder="Rychla poznamka behem hovoru..."
                            ></textarea>
                            <div class="flex items-center gap-2">
                                <button type="submit" class="js-active-call-quick-note-submit rounded-md bg-emerald-500 px-3 py-2 text-xs font-medium text-slate-950 sm:mt-0">
                                    Ulozit
                                </button>
                                <span class="js-active-call-quick-note-status text-xs text-emerald-300" aria-live="polite"></span>
                            </div>
                        </form>
                    </details>
                </div>
                <div class="col-start-2 row-start-1 row-span-2 flex items-stretch justify-end" data-row-link-ignore>
                    <form method="POST" action="{{ route('calls.end', $activeCallBanner) }}" class="h-full w-full" data-row-link-ignore>
                        @csrf
                        @if (request()->routeIs('caller-mode.*'))
                            <input type="hidden" name="caller_mode" value="1">
                        @endif
                        <button type="submit" class="inline-flex h-full aspect-square items-center justify-center rounded-lg bg-rose-500 text-white ring-1 ring-rose-300/40 hover:bg-rose-400" title="Ukoncit hovor" aria-label="Ukoncit hovor">
                            <svg viewBox="0 0 20 20" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4.5 12.5a9 9 0 0 1 11 0" />
                                <path d="M6.5 11.5 5 15l2.3 1.1 1.2-2.2" />
                                <path d="M13.5 11.5 15 15l-2.3 1.1-1.2-2.2" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </a>
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
        document.addEventListener('DOMContentLoaded', function () {
            const syncSplitInput = function (mainInput) {
                if (!mainInput) return;
                const dateId = mainInput.getAttribute('data-split-date');
                const timeId = mainInput.getAttribute('data-split-time');
                if (!dateId || !timeId) return;

                const dateInput = document.getElementById(dateId);
                const timeInput = document.getElementById(timeId);
                if (!dateInput || !timeInput) return;

                const toMain = function () {
                    const dateValue = String(dateInput.value || '').trim();
                    const timeValue = String(timeInput.value || '').trim();
                    mainInput.value = (dateValue && timeValue) ? (dateValue + 'T' + timeValue) : '';
                };

                const toSplit = function () {
                    const raw = String(mainInput.value || '').trim();
                    if (!raw) {
                        dateInput.value = '';
                        timeInput.value = '';
                        return;
                    }

                    const parts = raw.split('T');
                    dateInput.value = parts[0] || '';
                    timeInput.value = (parts[1] || '').slice(0, 5);
                };

                toSplit();
                mainInput.addEventListener('change', toSplit);
                mainInput.addEventListener('input', toSplit);
                dateInput.addEventListener('change', toMain);
                dateInput.addEventListener('input', toMain);
                timeInput.addEventListener('change', toMain);
                timeInput.addEventListener('input', toMain);

                const form = mainInput.closest('form');
                if (form) {
                    form.addEventListener('submit', toMain);
                }
            };

            document.querySelectorAll('.js-datetime-main[data-split-date][data-split-time]').forEach(syncSplitInput);
        });

        window.crmSuccessFeedback = function () {
            try {
                if (typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function') {
                    navigator.vibrate([18, 24, 18]);
                }
            } catch (error) {
            }

            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = 880;
                gain.gain.value = 0.0001;
                osc.connect(gain);
                gain.connect(ctx.destination);
                const now = ctx.currentTime;
                gain.gain.exponentialRampToValueAtTime(0.02, now + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.09);
                osc.start(now);
                osc.stop(now + 0.1);
                window.setTimeout(function () {
                    ctx.close().catch?.(() => {});
                }, 120);
            } catch (error) {
            }
        };

        document.addEventListener('click', function (event) {
            const target = event.target instanceof Element ? event.target : event.target?.parentElement;
            if (!target) return;

            const interactive = target.closest('[data-row-link-ignore], a, button, input, select, textarea, label, form');
            if (interactive) return;

            const row = target.closest('[data-row-link]');
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

        document.addEventListener('DOMContentLoaded', function () {
            const shell = document.getElementById('crm-shell');
            const toggle = document.getElementById('crm-sidebar-toggle');
            if (!shell || !toggle) return;

            const storageKey = 'crm.sidebar.collapsed';
            const applyState = function (collapsed) {
                shell.classList.toggle('is-collapsed', collapsed);
                document.documentElement.classList.toggle('crm-sidebar-collapsed', collapsed);
                const chevron = toggle.querySelector('.crm-sidebar-toggle-chevron');
                if (chevron) {
                    chevron.setAttribute('d', collapsed ? 'M7 4 13 10 7 16' : 'M13 4 7 10 13 16');
                }
                toggle.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
                toggle.setAttribute('title', collapsed ? 'Rozbalit bocni listu' : 'Sbalit bocni listu');
                toggle.setAttribute('aria-label', collapsed ? 'Rozbalit bocni listu' : 'Sbalit bocni listu');
            };

            applyState(document.documentElement.classList.contains('crm-sidebar-collapsed'));

            toggle.addEventListener('click', function () {
                const collapsed = !shell.classList.contains('is-collapsed');
                applyState(collapsed);
                window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
            });
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
                                if (typeof window.crmSuccessFeedback === 'function') {
                                    window.crmSuccessFeedback();
                                }
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

        @if (session('status'))
            window.setTimeout(function () {
                if (typeof window.crmSuccessFeedback === 'function') {
                    window.crmSuccessFeedback();
                }
            }, 80);
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
