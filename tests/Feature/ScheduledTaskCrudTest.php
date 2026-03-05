<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ScheduledTask;
use App\Models\TaskRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledTaskCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_tasks(): void
    {
        ScheduledTask::create($this->validTaskData(['name' => 'My Task']));

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('My Task');
    }

    public function test_store_creates_task(): void
    {
        $this->post(route('tasks.store'), $this->validTaskData())
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('scheduled_tasks', ['name' => 'Test Task']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->post(route('tasks.store'), [])
            ->assertSessionHasErrors(['name', 'command_type', 'command', 'cron_expression']);
    }

    public function test_store_rejects_invalid_cron_expression(): void
    {
        $this->post(route('tasks.store'), $this->validTaskData(['cron_expression' => 'not-valid']))
            ->assertSessionHasErrors(['cron_expression']);
    }

    public function test_update_modifies_task(): void
    {
        $task = ScheduledTask::create($this->validTaskData());

        $this->put(route('tasks.update', $task), $this->validTaskData(['name' => 'Updated Name']))
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('scheduled_tasks', ['id' => $task->id, 'name' => 'Updated Name']);
    }

    public function test_destroy_deletes_task_and_cascades_runs(): void
    {
        $task = ScheduledTask::create($this->validTaskData());
        TaskRun::create([
            'scheduled_task_id' => $task->id,
            'started_at' => now(),
            'status' => 'success',
            'triggered_by' => 'manual',
        ]);

        $this->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseMissing('scheduled_tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('task_runs', ['scheduled_task_id' => $task->id]);
    }

    public function test_toggle_flips_enabled_state(): void
    {
        $task = ScheduledTask::create($this->validTaskData(['is_enabled' => true]));

        $this->post(route('tasks.toggle', $task));
        $this->assertDatabaseHas('scheduled_tasks', ['id' => $task->id, 'is_enabled' => false]);

        $this->post(route('tasks.toggle', $task));
        $this->assertDatabaseHas('scheduled_tasks', ['id' => $task->id, 'is_enabled' => true]);
    }

    public function test_show_displays_run_history(): void
    {
        $task = ScheduledTask::create($this->validTaskData());
        TaskRun::create([
            'scheduled_task_id' => $task->id,
            'started_at' => now(),
            'status' => 'success',
            'triggered_by' => 'manual',
            'output' => 'hello world',
        ]);

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('hello world');
    }

    public function test_index_filters_by_project(): void
    {
        $project = Project::create(['name' => 'My Project']);
        ScheduledTask::create($this->validTaskData(['name' => 'Project Task', 'project_id' => $project->id]));
        ScheduledTask::create($this->validTaskData(['name' => 'Other Task']));

        $this->get(route('tasks.index', ['project_id' => $project->id]))
            ->assertOk()
            ->assertSee('Project Task')
            ->assertDontSee('Other Task');
    }

    public function test_index_filters_uncategorised(): void
    {
        $project = Project::create(['name' => 'My Project']);
        ScheduledTask::create($this->validTaskData(['name' => 'Project Task', 'project_id' => $project->id]));
        ScheduledTask::create($this->validTaskData(['name' => 'Uncategorised Task']));

        $this->get(route('tasks.index', ['project' => 'none']))
            ->assertOk()
            ->assertSee('Uncategorised Task')
            ->assertDontSee('Project Task');
    }

    private function validTaskData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Task',
            'command_type' => 'shell',
            'command' => 'echo hello',
            'cron_expression' => '* * * * *',
            'is_enabled' => true,
        ], $overrides);
    }
}
