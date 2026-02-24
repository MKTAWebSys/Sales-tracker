<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Call CRM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
            <div>
                <a href="{{ route('home') }}" class="text-lg font-semibold">Call CRM</a>
                <p class="text-xs text-slate-500">MVP evidence obchodních callů</p>
            </div>
            <nav class="flex flex-wrap gap-2 text-sm">
                @php
                    $links = [
                        ['Dashboard', 'dashboard'],
                        ['Firmy', 'companies.index'],
                        ['Hovory', 'calls.index'],
                        ['Follow-upy', 'follow-ups.index'],
                        ['Předání leadů', 'lead-transfers.index'],
                        ['Schůzky', 'meetings.index'],
                    ];
                @endphp
                @foreach ($links as [$label, $routeName])
                    <a href="{{ route($routeName) }}" class="rounded-md px-3 py-2 {{ request()->routeIs($routeName) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @yield('content')
    </main>
</body>
</html>
