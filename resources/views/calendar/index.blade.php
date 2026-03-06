@extends('layouts.app')

@section('title', 'Calendar — ' . $month->format('F Y'))

@section('content')
<div>

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
                $dayUpcoming = $upcoming[$dateKey] ?? [];
            @endphp

            <div class="min-h-24 p-2 {{ !$loop->last ? 'border-r border-stone-100' : '' }} {{ $isCurrentMonth ? 'bg-white' : 'bg-stone-50/50' }}">
                {{-- Date number --}}
                <div class="flex justify-end mb-1.5">
                    <span class="text-xs font-semibold w-6 h-6 flex items-center justify-center rounded-full
                        {{ $isToday ? 'bg-amber-400 text-amber-950' : ($isCurrentMonth ? 'text-stone-700' : 'text-stone-300') }}">
                        {{ $day->day }}
                    </span>
                </div>

                {{-- Task list --}}
                <div class="space-y-0.5">
                    @foreach($dayUpcoming as $entry)
                    <div class="flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-blue-50 border border-blue-100 min-w-0">
                        <span class="text-xs text-stone-400 font-mono flex-shrink-0">{{ $entry['time']->format('H:i') }}</span>
                        <span class="text-xs text-blue-700 font-medium truncate">{{ $entry['task']->name }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>

</div>
@endsection
