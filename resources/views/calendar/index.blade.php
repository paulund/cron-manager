@extends('layouts.app')

@section('title', 'Calendar — ' . $month->format('F Y'))

@section('content')
<div x-data="{ activeDay: null, activeRuns: [], activeUpcoming: [] }">

    {{-- Header: month nav + title --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('calendar', ['month' => $prevMonth]) }}"
               class="p-2 rounded-xl border border-stone-200 text-stone-400 hover:bg-stone-50 hover:text-stone-700 transition-colors">
                &larr;
            </a>
            <h1 class="text-2xl font-bold text-stone-900">{{ $month->format('F Y') }}</h1>
            <a href="{{ route('calendar', ['month' => $nextMonth]) }}"
               class="p-2 rounded-xl border border-stone-200 text-stone-400 hover:bg-stone-50 hover:text-stone-700 transition-colors">
                &rarr;
            </a>
        </div>
        <a href="{{ route('calendar') }}" class="text-sm text-stone-400 hover:text-stone-700 transition-colors">Today</a>
    </div>

    {{-- Day-of-week headers --}}
    <div class="grid grid-cols-7 mb-1">
        @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
        <div class="py-2 text-center text-xs font-semibold text-stone-400 uppercase tracking-wider">{{ $dayName }}</div>
        @endforeach
    </div>

    {{-- Calendar grid --}}
    <div class="border border-stone-200 rounded-2xl overflow-hidden">
        @foreach($weeks as $weekIndex => $week)
        <div class="grid grid-cols-7 {{ !$loop->last ? 'border-b border-stone-100' : '' }}">
            @foreach($week as $day)
            @php
                $dateKey = $day->format('Y-m-d');
                $isCurrentMonth = $day->month === $month->month;
                $isToday = $day->isToday();
                $dayRuns = $taskRuns->get($dateKey, collect());
                $dayUpcoming = $upcoming[$dateKey] ?? [];
                $hasRuns = $dayRuns->isNotEmpty();
                $hasUpcoming = count($dayUpcoming) > 0;
                $successCount = $dayRuns->where('status', 'success')->count();
                $failedCount = $dayRuns->where('status', 'failed')->count();
                $runningCount = $dayRuns->where('status', 'running')->count();
            @endphp

            <div
                class="min-h-24 p-2 cursor-pointer transition-colors {{ !$loop->last ? 'border-r border-stone-100' : '' }} {{ $isCurrentMonth ? 'bg-white hover:bg-stone-50' : 'bg-stone-50/50 hover:bg-stone-50' }}"
                @click="
                    activeDay = '{{ $day->format('D, d M') }}';
                    activeRuns = {{ json_encode($dayRuns->map(fn ($r) => [
                        'task' => $r->scheduledTask?->name ?? 'Unknown',
                        'status' => $r->status,
                        'time' => $r->started_at->format('H:i'),
                        'duration' => $r->duration() ? round($r->duration()) . 's' : null,
                        'exit_code' => $r->exit_code,
                    ])->values()) }};
                    activeUpcoming = {{ json_encode(collect($dayUpcoming)->map(fn ($u) => [
                        'task' => $u['task']->name,
                        'time' => $u['time']->format('H:i'),
                    ])->values()) }};
                "
            >
                {{-- Date number --}}
                <div class="flex justify-end mb-1.5">
                    <span class="text-xs font-semibold w-6 h-6 flex items-center justify-center rounded-full
                        {{ $isToday ? 'bg-amber-400 text-amber-950' : ($isCurrentMonth ? 'text-stone-700' : 'text-stone-300') }}">
                        {{ $day->day }}
                    </span>
                </div>

                {{-- Run indicators --}}
                <div class="space-y-0.5">
                    @if($successCount > 0)
                    <div class="flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-emerald-50 border border-emerald-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                        <span class="text-xs text-emerald-700 font-medium truncate">{{ $successCount }} ok</span>
                    </div>
                    @endif

                    @if($failedCount > 0)
                    <div class="flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-red-50 border border-red-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
                        <span class="text-xs text-red-700 font-medium truncate">{{ $failedCount }} failed</span>
                    </div>
                    @endif

                    @if($runningCount > 0)
                    <div class="flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-amber-50 border border-amber-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                        <span class="text-xs text-amber-700 font-medium truncate">{{ $runningCount }} running</span>
                    </div>
                    @endif

                    @if($hasUpcoming && !$hasRuns)
                    <div class="flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-blue-50 border border-blue-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-300 flex-shrink-0"></span>
                        <span class="text-xs text-blue-500 font-medium truncate">{{ count($dayUpcoming) }} scheduled</span>
                    </div>
                    @elseif($hasUpcoming)
                    <div class="flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-blue-50 border border-blue-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-300 flex-shrink-0"></span>
                        <span class="text-xs text-blue-500 font-medium truncate">+{{ count($dayUpcoming) }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>

    {{-- Day detail panel --}}
    <div
        x-show="activeDay !== null"
        x-cloak
        class="mt-6 rounded-2xl border border-stone-200 bg-white overflow-hidden"
    >
        <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100">
            <h2 class="font-semibold text-stone-900" x-text="activeDay"></h2>
            <button @click="activeDay = null; activeRuns = []; activeUpcoming = []"
                    class="text-stone-400 hover:text-stone-700 transition-colors text-lg leading-none">&times;</button>
        </div>

        <div class="divide-y divide-stone-100">

            {{-- Past runs section --}}
            <template x-if="activeRuns.length > 0">
                <div class="px-5 py-4">
                    <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-3">Runs</p>
                    <div class="space-y-2">
                        <template x-for="run in activeRuns" :key="run.task + run.time">
                            <div class="flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full flex-shrink-0"
                                      :class="{
                                          'bg-emerald-400': run.status === 'success',
                                          'bg-red-400': run.status === 'failed',
                                          'bg-amber-400': run.status === 'running',
                                          'bg-stone-300': !['success','failed','running'].includes(run.status)
                                      }"></span>
                                <span class="text-sm font-medium text-stone-700 flex-1 truncate" x-text="run.task"></span>
                                <span class="text-xs text-stone-400 font-mono" x-text="run.time"></span>
                                <template x-if="run.duration">
                                    <span class="text-xs text-stone-400" x-text="run.duration"></span>
                                </template>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                      :class="{
                                          'bg-emerald-50 text-emerald-700': run.status === 'success',
                                          'bg-red-50 text-red-700': run.status === 'failed',
                                          'bg-amber-50 text-amber-700': run.status === 'running',
                                          'bg-stone-100 text-stone-500': !['success','failed','running'].includes(run.status)
                                      }"
                                      x-text="run.status"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Upcoming section --}}
            <template x-if="activeUpcoming.length > 0">
                <div class="px-5 py-4">
                    <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider mb-3">Upcoming</p>
                    <div class="space-y-2">
                        <template x-for="item in activeUpcoming" :key="item.task + item.time">
                            <div class="flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full bg-blue-300 flex-shrink-0"></span>
                                <span class="text-sm font-medium text-stone-500 flex-1 truncate" x-text="item.task"></span>
                                <span class="text-xs text-stone-400 font-mono" x-text="item.time"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-500 font-medium">scheduled</span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Empty state --}}
            <template x-if="activeRuns.length === 0 && activeUpcoming.length === 0">
                <div class="px-5 py-8 text-center">
                    <p class="text-stone-400 text-sm">No runs on this day.</p>
                </div>
            </template>

        </div>
    </div>

    {{-- Legend --}}
    <div class="flex items-center gap-5 mt-5 text-xs text-stone-400">
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> Succeeded</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-400"></span> Failed</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Running</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-300"></span> Scheduled</span>
    </div>

</div>
@endsection
