@extends('layouts.app')

@section('title', 'New Project')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-4 mb-6">
        <h1 class="text-2xl font-semibold">New Project</h1>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('projects.store') }}" class="space-y-5">
            @csrf
            @include('projects._form')

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Create Project</button>
                <a href="{{ route('projects.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
