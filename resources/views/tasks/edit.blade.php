@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
<div class="max-w-2xl">
    <a href="{{ route('tasks.show', $task) }}" class="text-sm text-stone-400 hover:text-stone-700 transition-colors mb-4 inline-block">&larr; Back to task</a>
    <h1 class="text-3xl font-bold text-stone-900 mb-8">Edit: {{ $task->name }}</h1>
    <div class="rounded-2xl border border-stone-200 bg-white p-8">
        <form method="POST" action="{{ route('tasks.update', $task) }}">
            @csrf
            @method('PUT')
            @include('tasks._form', ['task' => $task])
            <div class="flex items-center gap-3 pt-4">
                <button type="submit" class="btn-primary">Update Task</button>
                <a href="{{ route('tasks.show', $task) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
