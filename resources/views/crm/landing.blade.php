<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Call CRM MVP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto flex min-h-screen max-w-5xl flex-col justify-center px-6 py-16">
        <div class="mb-6 flex items-center justify-end">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-md bg-cyan-400 px-4 py-2 text-sm font-medium text-slate-950">
                    Otevrit CRM
                </a>
            @else
                <a href="{{ route('login') }}" class="rounded-md bg-cyan-400 px-4 py-2 text-sm font-medium text-slate-950">
                    Prihlasit se
                </a>
            @endauth
        </div>
        <p class="mb-4 text-sm uppercase tracking-[0.2em] text-cyan-300">Laravel + Blade + Breeze</p>
        <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">Call CRM MVP</h1>
        <p class="mt-6 max-w-2xl text-base leading-7 text-slate-300">
            Připravený základ projektu a struktury modulů pro evidenci firem, hovorů, follow-upů, předání leadů a schůzek.
        </p>
        <div class="mt-10 rounded-2xl border border-slate-800 bg-slate-900/70 p-5 text-sm text-slate-300">
            Další krok: dokončit instalaci podle README (PHP, Composer, Node.js, PostgreSQL, Breeze, migrace, build assets).
        </div>
    </div>
</body>
</html>
