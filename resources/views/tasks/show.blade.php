@extends('layouts.app')

@section('title', $task->name)

@section('content')

{{-- Header card --}}
<div class="rounded-2xl border border-stone-200 bg-white p-6 mb-6">
    <div class="flex items-start justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1">
                <h1 class="text-2xl font-bold text-stone-900">{{ $task->name }}</h1>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full
                    {{ $task->command_type === 'claude' ? 'bg-blue-50 text-blue-600 border border-blue-100' : ($task->command_type === 'copilot' ? 'bg-purple-50 text-purple-600 border border-purple-100' : 'bg-stone-100 text-stone-500') }}">
                    {{ $task->command_type }}
                </span>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $task->is_enabled ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-stone-100 text-stone-400 border border-stone-200' }}">
                    {{ $task->is_enabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>
            @if($task->description)
            <p class="text-stone-500">{{ $task->description }}</p>
            @endif
        </div>
        <div class="flex gap-2 ml-4 shrink-0">
            <a href="{{ route('tasks.edit', $task) }}" class="btn-secondary">Edit</a>
            <form method="POST" action="{{ route('tasks.run', $task) }}">
                @csrf
                <button class="btn-primary">&#9654; Run Now</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-x-8 gap-y-4 mt-6 text-sm">
        <div>
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-1.5">Command</p>
            <pre class="font-mono text-xs bg-stone-900 text-stone-100 p-3 rounded-xl overflow-auto">{{ $task->command }}</pre>
        </div>
        <div>
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-1.5">Working Directory</p>
            <code class="font-mono text-xs text-stone-600">{{ $task->working_directory ?? 'App root' }}</code>
        </div>
        <div>
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-1.5">Schedule</p>
            <code class="cron-pill">{{ $task->cron_expression }}</code>
        </div>
        <div>
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-1.5">Next Run</p>
            <p class="text-stone-700">{{ $task->nextRunAt()?->format('D d M Y H:i') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-1.5">Last Run</p>
            <p class="text-stone-700">{{ $task->last_run_at?->diffForHumans() ?? 'Never' }}</p>
        </div>
        @if($task->project)
        <div>
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-1.5">Project</p>
            <a href="{{ route('tasks.index', ['project_id' => $task->project->id]) }}" class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100 hover:bg-amber-100 transition-colors">
                {{ $task->project->name }}
            </a>
        </div>
        @endif

        @if($task->tags->isNotEmpty())
        <div>
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-1.5">Tags</p>
            <div class="flex flex-wrap gap-1">
                @foreach($task->tags as $tag)
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">{{ $tag->name }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if($task->dependsOn)
        <div>
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-1.5">Depends On</p>
            <a href="{{ route('tasks.show', $task->dependsOn) }}" class="text-sm text-indigo-600 hover:underline">{{ $task->dependsOn->name }}</a>
        </div>
        @endif

        @if($task->isPaused())
        <div>
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-1.5">Snoozed Until</p>
            <p class="text-sm text-amber-600 font-medium">{{ $task->paused_until->format('D d M Y H:i') }}</p>
        </div>
        @endif

        @if(!empty($task->env_vars))
        <div class="col-span-2">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-1.5">Environment Variables</p>
            <div class="flex flex-wrap gap-2">
                @foreach($task->env_vars as $pair)
                    @if(!empty($pair['key']))
                    <code class="text-xs bg-stone-100 text-stone-700 px-2 py-0.5 rounded font-mono">{{ $pair['key'] }}=<span class="text-stone-500">{{ $pair['value'] }}</span></code>
                    @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Run history --}}
<h2 class="text-lg font-bold text-stone-900 mb-3">Run History</h2>

@if($runs->isEmpty())
<div class="text-center py-12 rounded-2xl border border-stone-200">
    <p class="text-stone-400">No runs yet. Click "Run Now" or wait for the scheduler.</p>
</div>
@else
<div class="space-y-2">
    @foreach($runs as $run)
    <div class="rounded-2xl border {{ $run->status === 'failed' ? 'border-red-200 bg-red-50/30' : 'border-stone-200 bg-white' }} p-4" x-data="{ expanded: false }">
        <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                        {{ $run->status === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($run->status === 'running' ? 'bg-stone-100 text-stone-600 border border-stone-200' : 'bg-red-100 text-red-600 border border-red-200') }}">
                    {{ ucfirst($run->status) }}
                </span>
                <span class="text-sm text-stone-600">{{ $run->started_at->format('d M Y H:i:s') }}</span>
                @if($run->duration() !== null)
                <span class="text-xs text-stone-400">{{ number_format($run->duration(), 1) }}s</span>
                @endif
                <span class="text-xs text-stone-400 capitalize">{{ $run->triggered_by }}</span>
            </div>
            <span class="text-stone-400 text-xs" x-text="expanded ? '▲ Hide' : '▼ Output'"></span>
        </div>

        <div x-show="expanded" x-transition>
            @if($run->output)
            <pre class="mt-3 font-mono text-xs bg-stone-900 text-stone-100 p-4 rounded-xl overflow-auto max-h-96">{{ $run->output }}</pre>
            @else
            <p class="mt-3 text-sm text-stone-400 italic">No output captured.</p>
            @endif
        </div>
    </div>
    @endforeach
</div>

<div class="mt-4">
    {{ $runs->links() }}
</div>
@endif

<div class="mt-6">
    <a href="{{ route('tasks.index') }}" class="text-sm text-stone-400 hover:text-stone-700 transition-colors">&larr; Back to all tasks</a>
</div>

@endsection
