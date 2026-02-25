<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Call CRM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
            <div>
                <a href="{{ route('home') }}" class="text-lg font-semibold">Call CRM</a>
                <p class="text-xs text-slate-500">MVP evidence obchodnich callu</p>
            </div>
            <nav class="flex flex-wrap items-center gap-2 text-sm">
                @php
                    $links = [
                        ['Dashboard', 'dashboard'],
                        ['Firmy', 'companies.index'],
                        ['Hovory', 'calls.index'],
                        ['Follow-upy', 'follow-ups.index'],
                        ['Predani leadu', 'lead-transfers.index'],
                        ['Schuzky', 'meetings.index'],
                    ];

                    $currentCompanyParam = request()->route('company');
                    $currentCompanyId = is_object($currentCompanyParam) ? ($currentCompanyParam->id ?? null) : (is_numeric($currentCompanyParam) ? (int) $currentCompanyParam : null);
                @endphp

                @auth
                    <a href="{{ route('companies.next-mine', array_filter(['current_company_id' => $currentCompanyId, 'skip_lost' => 1])) }}"
                       class="rounded-md bg-emerald-600 px-3 py-2 font-medium text-white hover:bg-emerald-700">
                        Moje dalsi firma
                    </a>
                @endauth

                @foreach ($links as [$label, $routeName])
                    <a href="{{ route($routeName) }}" class="rounded-md px-3 py-2 {{ request()->routeIs($routeName) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $label }}
                    </a>
                @endforeach

                @auth
                    @if (auth()->user()?->isManager())
                        <a href="{{ route('users.index') }}" class="rounded-md px-3 py-2 {{ request()->routeIs('users.*') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            Uzivatele
                        </a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @yield('content')
    </main>

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
                    ×
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

            // Let the browser focus the field first, then open the picker from the whole input area.
            window.setTimeout(function () {
                try {
                    input.showPicker();
                } catch (error) {
                    // Some browsers block repeated picker calls; ignore and keep default behavior.
                }
            }, 0);
        });

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
