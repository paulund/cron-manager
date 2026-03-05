<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cron Manager')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-white text-stone-900 min-h-screen antialiased">
    <header class="border-b border-stone-100">
        <div class="max-w-5xl mx-auto px-8 h-16 flex items-center justify-between">
            <a href="{{ route('tasks.index') }}" class="flex items-center gap-3 font-semibold text-stone-900 text-lg hover:text-stone-700 transition-colors">
                <span class="text-2xl">🕐</span>
                Cron Manager
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('calendar') }}" class="text-sm text-stone-400 hover:text-stone-700 transition-colors">Calendar</a>
                <a href="{{ route('health') }}" class="text-sm text-stone-400 hover:text-stone-700 transition-colors">Health</a>
                <a href="{{ route('tasks.create') }}" class="px-4 py-2 rounded-full text-sm font-semibold bg-amber-400 text-amber-950 hover:bg-amber-300 transition-colors">
                    + New Task
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-8 py-10">
        <x-flash-message />
        @yield('content')
    </main>

    <footer class="max-w-5xl mx-auto px-8 py-6 mt-4 border-t border-stone-100">
        <p class="text-xs text-stone-300">Cron Manager &mdash; local scheduler UI</p>
    </footer>
</body>

</html>
