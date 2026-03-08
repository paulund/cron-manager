@extends('layouts.app')

@section('title', 'Create Task')

@section('content')
<div class="max-w-2xl">
    <a href="{{ route('tasks.index') }}" class="text-sm text-stone-400 hover:text-stone-700 transition-colors mb-4 inline-block">&larr; Back to tasks</a>
    <h1 class="text-3xl font-bold text-stone-900 mb-8">New Scheduled Task</h1>
    <div class="rounded-2xl border border-stone-200 bg-white p-8">
        <form method="POST" action="{{ route('tasks.store') }}">
            @csrf
            @include('tasks._form', ['task' => null])
            <div class="flex items-center gap-3 pt-4">
                <button type="submit" class="btn-primary">Create Task</button>
                <a href="{{ route('tasks.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
