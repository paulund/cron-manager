@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold">Projects</h1>
    <a href="{{ route('projects.create') }}" class="btn-primary">+ New Project</a>
</div>

@if($projects->isEmpty())
    <div class="text-center py-16 text-gray-500">
        <p class="text-lg">No projects yet.</p>
        <a href="{{ route('projects.create') }}" class="btn-primary mt-4 inline-flex">Create your first project</a>
    </div>
@else
    <div class="card overflow-hidden p-0">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Name</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Description</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Tasks</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($projects as $project)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium">
                        <a href="{{ route('tasks.index', ['project_id' => $project->id]) }}" class="hover:text-indigo-600">
                            {{ $project->name }}
                        </a>
                    </td>
                    <td class="px-5 py-3 text-gray-500 max-w-xs truncate">
                        {{ $project->description ?? '—' }}
                    </td>
                    <td class="px-5 py-3">
                        <a href="{{ route('tasks.index', ['project_id' => $project->id]) }}" class="badge badge-gray hover:bg-gray-200">
                            {{ $project->tasks_count }} {{ Str::plural('task', $project->tasks_count) }}
                        </a>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('projects.edit', $project) }}" class="btn-secondary">Edit</a>

                            <div x-data="{ confirm: false }">
                                <button x-show="!confirm" @click="confirm = true" class="btn-danger">Delete</button>
                                <span x-show="confirm" x-cloak class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('projects.destroy', $project) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger">Yes, delete</button>
                                    </form>
                                    <button @click="confirm = false" class="btn-secondary">Cancel</button>
                                </span>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
