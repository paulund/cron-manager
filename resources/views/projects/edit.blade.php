@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-4 mb-6">
        <h1 class="text-2xl font-semibold">Edit Project</h1>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-5">
            @csrf
            @method('PUT')
            @include('projects._form')

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="{{ route('projects.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
