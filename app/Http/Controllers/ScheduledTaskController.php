<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduledTaskRequest;
use App\Http\Requests\UpdateScheduledTaskRequest;
use App\Models\Project;
use App\Models\ScheduledTask;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ScheduledTaskController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::withCount('tasks')->orderBy('name')->get();
        $uncategorisedCount = ScheduledTask::whereNull('project_id')->count();

        $stats = [
            'total' => ScheduledTask::count(),
            'enabled' => ScheduledTask::where('is_enabled', true)->count(),
            'disabled' => ScheduledTask::where('is_enabled', false)->count(),
            'failing' => ScheduledTask::whereHas(
                'latestRun',
                fn (\Illuminate\Contracts\Database\Query\Builder $q) => $q->where('status', 'failed')
            )->count(),
        ];

        $tasks = ScheduledTask::with(['project', 'latestRun', 'tags'])
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->get('project') === 'none', fn ($q) => $q->whereNull('project_id'))
            ->when($request->filled('search'), fn ($q) => $q->where(function (\Illuminate\Contracts\Database\Query\Builder $q) use ($request): void {
                $q->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('command', 'like', '%'.$request->string('search').'%');
            }))
            ->when($request->get('status') === 'enabled', fn ($q) => $q->where('is_enabled', true))
            ->when($request->get('status') === 'disabled', fn ($q) => $q->where('is_enabled', false))
            ->when($request->get('status') === 'failing', fn ($q) => $q->whereHas(
                'latestRun',
                fn (\Illuminate\Contracts\Database\Query\Builder $q) => $q->where('status', 'failed')
            ))
            ->when($request->filled('tag'), fn ($q) => $q->whereHas(
                'tags',
                fn (\Illuminate\Contracts\Database\Query\Builder $q) => $q->where('name', $request->string('tag'))
            ))
            ->latest()
            ->paginate(20);

        $activeProjectId = $request->filled('project_id') ? $request->integer('project_id') : null;
        $activeFilter = $request->get('project');
        $search = $request->string('search')->toString();
        $statusFilter = $request->get('status', '');
        $tagFilter = $request->string('tag')->toString();
        $allTags = Tag::orderBy('name')->get();

        return view('tasks.index', ['tasks' => $tasks, 'projects' => $projects, 'uncategorisedCount' => $uncategorisedCount, 'activeProjectId' => $activeProjectId, 'activeFilter' => $activeFilter, 'stats' => $stats, 'search' => $search, 'statusFilter' => $statusFilter, 'tagFilter' => $tagFilter, 'allTags' => $allTags]);
    }

    public function create(): View
    {
        $projects = Project::orderBy('name')->get();
        $allTasks = ScheduledTask::orderBy('name')->get();
        $allTags = Tag::orderBy('name')->get();

        return view('tasks.create', ['projects' => $projects, 'allTasks' => $allTasks, 'allTags' => $allTags]);
    }

    public function store(StoreScheduledTaskRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tagNames = $validated['tags'] ?? [];
        unset($validated['tags']);

        $task = ScheduledTask::create([
            ...$validated,
            'is_enabled' => $request->boolean('is_enabled'),
            'prevent_overlap' => $request->boolean('prevent_overlap'),
            'notify_on_failure' => $request->boolean('notify_on_failure'),
        ]);

        $this->syncTags($task, $tagNames);

        return to_route('tasks.index')->with('success', 'Task created.');
    }

    public function show(ScheduledTask $task): View
    {
        $task->load(['project', 'tags', 'dependsOn']);
        $runs = $task->taskRuns()->latest('started_at')->paginate(20);

        return view('tasks.show', ['task' => $task, 'runs' => $runs]);
    }

    public function edit(ScheduledTask $task): View
    {
        $projects = Project::orderBy('name')->get();
        $allTasks = ScheduledTask::where('id', '!=', $task->id)->orderBy('name')->get();
        $allTags = Tag::orderBy('name')->get();

        return view('tasks.edit', ['task' => $task, 'projects' => $projects, 'allTasks' => $allTasks, 'allTags' => $allTags]);
    }

    public function update(UpdateScheduledTaskRequest $request, ScheduledTask $task): RedirectResponse
    {
        $validated = $request->validated();
        $tagNames = $validated['tags'] ?? [];
        unset($validated['tags']);

        $task->update([
            ...$validated,
            'is_enabled' => $request->boolean('is_enabled'),
            'prevent_overlap' => $request->boolean('prevent_overlap'),
            'notify_on_failure' => $request->boolean('notify_on_failure'),
        ]);

        $this->syncTags($task, $tagNames);

        return to_route('tasks.index')->with('success', 'Task updated.');
    }

    /** @param string[] $tagNames */
    private function syncTags(ScheduledTask $task, array $tagNames): void
    {
        $tagIds = collect($tagNames)
            ->filter()
            ->map(fn (string $name) => Tag::firstOrCreate(
                ['name' => trim($name)],
                ['color' => '#6366f1']
            )->id);

        $task->tags()->sync($tagIds);
    }

    public function destroy(ScheduledTask $task): RedirectResponse
    {
        $task->delete();

        return to_route('tasks.index')->with('success', 'Task deleted.');
    }
}
