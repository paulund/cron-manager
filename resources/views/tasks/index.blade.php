@extends('layouts.app')

@section('title', 'Scheduled Tasks')

@section('content')
<div class="flex gap-8">

    {{-- Sidebar --}}
    <aside class="w-44 shrink-0">
        <nav class="space-y-0.5">
            <a
                href="{{ route('tasks.index') }}"
                class="flex items-center justify-between px-3 py-2 rounded-xl text-sm {{ !$activeProjectId && $activeFilter !== 'none' ? 'bg-amber-50 text-amber-800 font-semibold' : 'text-stone-500 hover:bg-stone-50 hover:text-stone-800' }} transition-colors">
                <span>All Tasks</span>
            </a>

            @if($projects->isNotEmpty())
            <p class="px-3 pt-4 pb-1 text-xs font-semibold text-stone-400 uppercase tracking-wider">Projects</p>

            @foreach($projects as $project)
            <a
                href="{{ route('tasks.index', ['project_id' => $project->id]) }}"
                class="flex items-center justify-between px-3 py-2 rounded-xl text-sm {{ $activeProjectId === $project->id ? 'bg-amber-50 text-amber-800 font-semibold' : 'text-stone-500 hover:bg-stone-50 hover:text-stone-800' }} transition-colors">
                <span class="truncate">{{ $project->name }}</span>
                <span class="text-xs bg-stone-100 text-stone-500 rounded-full px-2 py-0.5 ml-1 shrink-0">{{ $project->tasks_count }}</span>
            </a>
            @endforeach
            @endif

            <a
                href="{{ route('tasks.index', ['project' => 'none']) }}"
                class="flex items-center justify-between px-3 py-2 rounded-xl text-sm {{ $activeFilter === 'none' ? 'bg-amber-50 text-amber-800 font-semibold' : 'text-stone-500 hover:bg-stone-50 hover:text-stone-800' }} transition-colors">
                <span>Uncategorised</span>
                <span class="text-xs bg-stone-100 text-stone-500 rounded-full px-2 py-0.5">{{ $uncategorisedCount }}</span>
            </a>
        </nav>
        <div class="mt-4 pt-4 border-t border-stone-100">
            <a href="{{ route('projects.create') }}" class="text-xs text-amber-600 hover:text-amber-800 px-3 transition-colors">+ New Project</a>
        </div>

        @if($allTags->isNotEmpty())
        <nav class="mt-4 pt-4 border-t border-stone-100 space-y-0.5">
            <p class="px-3 pb-1 text-xs font-semibold text-stone-400 uppercase tracking-wider">Tags</p>
            @foreach($allTags as $tag)
            <a
                href="{{ route('tasks.index', ['tag' => $tag->name]) }}"
                class="flex items-center justify-between px-3 py-1.5 rounded-xl text-sm {{ $tagFilter === $tag->name ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-stone-500 hover:bg-stone-50 hover:text-stone-800' }} transition-colors">
                <span class="truncate">{{ $tag->name }}</span>
            </a>
            @endforeach
        </nav>
        @endif
    </aside>

    {{-- Main content --}}
    <div class="flex-1 min-w-0">

        {{-- Stats strip --}}
        <div class="grid grid-cols-4 gap-3 mb-8">
            <div class="text-center py-4 rounded-2xl border border-stone-200 bg-white">
                <div class="text-2xl font-bold text-stone-800">{{ $stats['total'] }}</div>
                <div class="text-xs text-stone-400 mt-0.5 font-medium">Total</div>
            </div>
            <div class="text-center py-4 rounded-2xl border border-stone-200 bg-white">
                <div class="text-2xl font-bold text-emerald-500">{{ $stats['enabled'] }}</div>
                <div class="text-xs text-stone-400 mt-0.5 font-medium">Enabled</div>
            </div>
            <div class="text-center py-4 rounded-2xl border border-stone-200 bg-white">
                <div class="text-2xl font-bold text-stone-300">{{ $stats['disabled'] }}</div>
                <div class="text-xs text-stone-400 mt-0.5 font-medium">Disabled</div>
            </div>
            <div class="text-center py-4 rounded-2xl {{ $stats['failing'] > 0 ? 'border-red-200 bg-red-50/40' : 'border-stone-200 bg-white' }} border">
                <div class="text-2xl font-bold {{ $stats['failing'] > 0 ? 'text-red-500' : 'text-stone-300' }}">{{ $stats['failing'] }}</div>
                <div class="text-xs {{ $stats['failing'] > 0 ? 'text-red-400' : 'text-stone-400' }} mt-0.5 font-medium">Failed</div>
            </div>
        </div>

        {{-- Title + search bar --}}
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-stone-900">
                @if($activeProjectId)
                {{ $projects->firstWhere('id', $activeProjectId)?->name ?? 'Project' }}
                @elseif($activeFilter === 'none')
                Uncategorised
                @else
                Your tasks
                @endif
            </h1>
        </div>

        <form method="GET" action="{{ route('tasks.index') }}" class="flex items-center gap-2 mb-6">
            @if($activeProjectId)
            <input type="hidden" name="project_id" value="{{ $activeProjectId }}">
            @elseif($activeFilter === 'none')
            <input type="hidden" name="project" value="none">
            @endif
            @if($tagFilter)
            <input type="hidden" name="tag" value="{{ $tagFilter }}">
            @endif
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search tasks…"
                class="form-input flex-1 py-1.5">
            <select name="status" class="form-input py-1.5 w-40">
                <option value="" {{ $statusFilter === '' ? 'selected' : '' }}>All statuses</option>
                <option value="enabled" {{ $statusFilter === 'enabled' ? 'selected' : '' }}>Enabled</option>
                <option value="disabled" {{ $statusFilter === 'disabled' ? 'selected' : '' }}>Disabled</option>
                <option value="failing" {{ $statusFilter === 'failing' ? 'selected' : '' }}>Last run failed</option>
            </select>
            <button type="submit" class="btn-secondary py-1.5">Filter</button>
            @if($search || $statusFilter || $tagFilter)
            <a href="{{ route('tasks.index', array_filter(['project_id' => $activeProjectId, 'project' => $activeFilter])) }}" class="btn-secondary py-1.5 text-stone-400">Clear</a>
            @endif
        </form>

        {{-- Empty state --}}
        @if($tasks->isEmpty())
        <div class="text-center py-20 rounded-2xl border border-stone-200">
            <p class="text-2xl mb-3">⏰</p>
            <p class="text-stone-500 font-medium mb-1">No tasks yet.</p>
            <p class="text-stone-400 text-sm mb-5">Add your first scheduled task to get started.</p>
            <a href="{{ route('tasks.create') }}" class="px-4 py-2 rounded-full text-sm font-semibold bg-amber-400 text-amber-950 hover:bg-amber-300 transition-colors">+ Create first task</a>
        </div>

        {{-- Task card list --}}
        @else
        <div class="space-y-2.5">
            @foreach($tasks as $task)
            @php
            $hasFailed = $task->latestRun?->status === 'failed';
            $isDisabled = !$task->is_enabled;
            @endphp

            @if($isDisabled)
            <div class="flex items-center gap-5 p-5 rounded-2xl border border-stone-100 bg-stone-50/50 opacity-60 hover:opacity-80 transition-all">
                @elseif($hasFailed)
                <div class="flex items-center gap-5 p-5 rounded-2xl border border-red-200 bg-red-50/50 hover:shadow-sm transition-all">
                    @else
                    <div class="flex items-center gap-5 p-5 rounded-2xl border border-stone-200 bg-white hover:border-stone-300 hover:shadow-sm transition-all">
                        @endif

                        {{-- Status dot --}}
                        @if($isDisabled)
                        <div class="w-2.5 h-2.5 rounded-full bg-stone-300 flex-shrink-0 ring-4 ring-stone-100"></div>
                        @elseif($hasFailed)
                        <div class="w-2.5 h-2.5 rounded-full bg-red-400 flex-shrink-0 ring-4 ring-red-100"></div>
                        @else
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 flex-shrink-0 ring-4 ring-emerald-100"></div>
                        @endif

                        {{-- Name + meta --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('tasks.show', $task) }}" class="font-semibold text-stone-900 hover:text-amber-700 transition-colors">
                                    {{ $task->name }}
                                </a>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                {{ $task->command_type === 'claude' ? 'bg-blue-50 text-blue-600 border border-blue-100' : ($task->command_type === 'copilot' ? 'bg-purple-50 text-purple-600 border border-purple-100' : 'bg-stone-100 text-stone-500') }}">
                                    {{ $task->command_type }}
                                </span>
                                @if($task->project)
                                <a href="{{ route('tasks.index', ['project_id' => $task->project->id]) }}" class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100 hover:bg-amber-100 transition-colors">
                                    {{ $task->project->name }}
                                </a>
                                @endif
                                @if($hasFailed)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600 border border-red-200">Last run failed</span>
                                @endif
                                @if($task->isPaused())
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200">Snoozed</span>
                                @endif
                                @foreach($task->tags as $tag)
                                <a href="{{ route('tasks.index', ['tag' => $tag->name]) }}" class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100 hover:bg-indigo-100 transition-colors">{{ $tag->name }}</a>
                                @endforeach
                            </div>
                            @if($task->description)
                            <p class="text-sm text-stone-400 mt-0.5 truncate">{{ $task->description }}</p>
                            @endif
                        </div>

                        {{-- Cron + next run --}}
                        <div class="text-center flex-shrink-0 hidden md:block">
                            <code class="cron-pill">{{ $task->cron_expression }}</code>
                            <p class="text-xs text-stone-400 mt-1.5">
                                {{ $task->nextRunAt()?->format('D d M H:i') ?? ($isDisabled ? '—' : '—') }}
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a href="{{ route('tasks.edit', $task) }}" class="px-3 py-1.5 rounded-xl text-xs font-medium border border-stone-200 text-stone-500 hover:bg-stone-50 transition-colors">Edit</a>

                            <form method="POST" action="{{ route('tasks.toggle', $task) }}">
                                @csrf
                                <button class="px-3 py-1.5 rounded-xl text-xs font-medium border transition-colors
                                {{ $task->is_enabled ? 'border-stone-200 text-stone-500 hover:bg-stone-50' : 'border-amber-200 text-amber-700 hover:bg-amber-50' }}">
                                    {{ $task->is_enabled ? 'Disable' : 'Enable' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('tasks.run', $task) }}">
                                @csrf
                                <button class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-400 text-amber-950 hover:bg-amber-300 transition-colors">&#9654; Run</button>
                            </form>

                            <div x-data="{ confirm: false }">
                                <button x-show="!confirm" @click="confirm = true"
                                    class="px-3 py-1.5 rounded-xl text-xs font-medium border border-stone-200 text-stone-400 hover:text-red-400 hover:border-red-200 hover:bg-red-50 transition-colors">
                                    &#x2715;
                                </button>
                                <span x-show="confirm" x-cloak class="flex items-center gap-1.5">
                                    <form method="POST" action="{{ route('tasks.destroy', $task) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-red-100 text-red-600 border border-red-200 hover:bg-red-200 transition-colors">Delete</button>
                                    </form>
                                    <button @click="confirm = false" class="px-3 py-1.5 rounded-xl text-xs font-medium border border-stone-200 text-stone-400 hover:bg-stone-50 transition-colors">Cancel</button>
                                </span>
                            </div>
                        </div>

                    </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $tasks->links() }}
                </div>
                @endif

            </div>
        </div>
        @endsection
