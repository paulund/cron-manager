@extends('layouts.app')

@section('title', 'Scheduler Health')

@section('content')
<div class="max-w-2xl">

    <h1 class="text-2xl font-bold text-stone-900 mb-8">Scheduler Health</h1>

    {{-- Status card --}}
    @php
        $statusConfig = match($status) {
            'healthy' => [
                'border' => 'border-emerald-200',
                'bg'     => 'bg-emerald-50/50',
                'dot'    => 'bg-emerald-400 ring-emerald-100',
                'label'  => 'Running',
                'color'  => 'text-emerald-700',
                'desc'   => 'The scheduler is active and running every minute.',
            ],
            'warning' => [
                'border' => 'border-amber-200',
                'bg'     => 'bg-amber-50/50',
                'dot'    => 'bg-amber-400 ring-amber-100',
                'label'  => 'Delayed',
                'color'  => 'text-amber-700',
                'desc'   => 'The scheduler heartbeat is overdue. It may have missed a run.',
            ],
            'stale' => [
                'border' => 'border-red-200',
                'bg'     => 'bg-red-50/50',
                'dot'    => 'bg-red-400 ring-red-100',
                'label'  => 'Not running',
                'color'  => 'text-red-700',
                'desc'   => 'No heartbeat received in over 5 minutes. The cron job may be stopped.',
            ],
            default => [
                'border' => 'border-stone-200',
                'bg'     => 'bg-stone-50/50',
                'dot'    => 'bg-stone-300 ring-stone-100',
                'label'  => 'Never seen',
                'color'  => 'text-stone-600',
                'desc'   => 'No heartbeat has been recorded yet. The cron job has not run since the app started.',
            ],
        };
    @endphp

    <div class="flex items-start gap-5 p-6 rounded-2xl border {{ $statusConfig['border'] }} {{ $statusConfig['bg'] }} mb-6">
        <div class="mt-1 w-3 h-3 rounded-full flex-shrink-0 ring-4 {{ $statusConfig['dot'] }}"></div>
        <div class="flex-1">
            <div class="font-semibold text-lg {{ $statusConfig['color'] }}">{{ $statusConfig['label'] }}</div>
            <p class="text-sm text-stone-500 mt-0.5">{{ $statusConfig['desc'] }}</p>

            @if($lastSeen)
            <p class="text-xs text-stone-400 mt-3">
                Last heartbeat: <span class="font-medium text-stone-600">{{ $lastSeen->diffForHumans() }}</span>
                <span class="text-stone-300 mx-1">&middot;</span>
                {{ $lastSeen->format('D d M Y H:i:s') }}
            </p>
            @endif
        </div>
        <button
            onclick="window.location.reload()"
            class="flex-shrink-0 px-3 py-1.5 rounded-xl text-xs font-medium border border-stone-200 text-stone-500 hover:bg-stone-50 transition-colors">
            Refresh
        </button>
    </div>

    {{-- Platform scheduler (LaunchAgent / crontab / Task Scheduler) --}}
    <div class="flex items-start gap-3 px-5 py-4 rounded-2xl border {{ $schedulerInstalled ? 'border-emerald-200 bg-emerald-50/40' : 'border-stone-200 bg-white' }} mb-3">
        @if($schedulerInstalled)
        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
        <div>
            <span class="text-sm text-emerald-700 font-medium">
                @if(PHP_OS_FAMILY === 'Darwin') LaunchAgent installed
                @elseif(PHP_OS_FAMILY === 'Windows') Task Scheduler task installed
                @else Crontab entry installed via scheduler:install
                @endif
            </span>
            @if(PHP_OS_FAMILY === 'Darwin')
            <p class="text-xs text-stone-400 mt-0.5">
                {{ $schedulerLoaded ? 'Loaded and running in your login session — Keychain access available.' : 'Installed but not currently loaded.' }}
            </p>
            @endif
        </div>
        @else
        <svg class="w-4 h-4 text-stone-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
        <div>
            <span class="text-sm text-stone-500">
                @if(PHP_OS_FAMILY === 'Darwin') No LaunchAgent installed
                @elseif(PHP_OS_FAMILY === 'Windows') No Task Scheduler task installed
                @else Scheduler not installed via scheduler:install
                @endif
            </span>
            <p class="text-xs text-stone-400 mt-0.5">Run <code class="font-mono bg-stone-100 px-1 rounded">{{ $installCommand }}</code> to install.</p>
        </div>
        @endif
    </div>

    {{-- Double-firing warning (macOS only) --}}
    @if(PHP_OS_FAMILY === 'Darwin' && $schedulerInstalled && $cronInstalled)
    <div class="flex items-start gap-3 px-5 py-4 rounded-2xl border border-amber-200 bg-amber-50/50 mb-3">
        <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
        <div>
            <span class="text-sm text-amber-700 font-medium">Double-firing risk</span>
            <p class="text-xs text-stone-500 mt-0.5">Both the LaunchAgent and a crontab entry are active. The scheduler will fire twice per minute. Remove the crontab entry with <code class="font-mono bg-stone-100 px-1 rounded">crontab -e</code>.</p>
        </div>
    </div>
    @endif

    {{-- Crontab detection (Linux only) --}}
    @if(PHP_OS_FAMILY !== 'Darwin' && PHP_OS_FAMILY !== 'Windows')
    <div class="flex items-center gap-3 px-5 py-4 rounded-2xl border {{ $cronInstalled ? 'border-emerald-200 bg-emerald-50/40' : 'border-stone-200 bg-white' }} mb-3">
        @if($cronInstalled)
        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
        <span class="text-sm text-emerald-700 font-medium">Crontab entry detected for this project</span>
        @else
        <svg class="w-4 h-4 text-stone-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
        <span class="text-sm text-stone-500">No crontab entry found for this project</span>
        @endif
    </div>
    @endif

    {{-- Setup instructions (shown when not healthy) --}}
    @if($status !== 'healthy')
    <div class="rounded-2xl border border-stone-200 bg-white overflow-hidden mt-8">
        <div class="px-6 py-4 border-b border-stone-100">
            <h2 class="font-semibold text-stone-800">How to enable the scheduler</h2>
            <p class="text-sm text-stone-400 mt-0.5">Add a crontab entry to run the Laravel scheduler every minute.</p>
        </div>

        <div class="p-6 space-y-6">

            {{-- Step 1 --}}
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold flex items-center justify-center flex-shrink-0">1</span>
                    <span class="text-sm font-semibold text-stone-700">Open your crontab</span>
                </div>
                <div class="ml-7">
                    <x-code-block>crontab -e</x-code-block>
                    <p class="text-xs text-stone-400 mt-2">This opens the crontab in your default editor (usually <code class="font-mono bg-stone-100 px-1 rounded">vi</code>). Press <kbd class="px-1 py-0.5 text-xs font-mono bg-stone-100 rounded border border-stone-200">i</kbd> to enter insert mode.</p>
                </div>
            </div>

            {{-- Step 2 --}}
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold flex items-center justify-center flex-shrink-0">2</span>
                    <span class="text-sm font-semibold text-stone-700">Add this line</span>
                </div>
                <div class="ml-7">
                    <x-code-block copyable>{{ $cronEntry }}</x-code-block>
                    <p class="text-xs text-stone-400 mt-2">Output is discarded so failures are silent — the app won't fill your mail spool.</p>
                </div>
            </div>

            {{-- Step 3 --}}
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold flex items-center justify-center flex-shrink-0">3</span>
                    <span class="text-sm font-semibold text-stone-700">Save and verify</span>
                </div>
                <div class="ml-7">
                    <p class="text-xs text-stone-500 mb-2">In vi: press <kbd class="px-1 py-0.5 text-xs font-mono bg-stone-100 rounded border border-stone-200">Esc</kbd> then type <code class="font-mono bg-stone-100 px-1 rounded">:wq</code> and press Enter. Then confirm:</p>
                    <x-code-block>crontab -l</x-code-block>
                    <p class="text-xs text-stone-400 mt-2">Come back here after a minute or two — the status above will turn green once the scheduler fires.</p>
                </div>
            </div>

        </div>
    </div>
    @endif

</div>
@endsection
