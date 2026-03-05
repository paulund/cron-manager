<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ScheduledTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_projects_with_task_counts(): void
    {
        $project = Project::create(['name' => 'My Project']);
        ScheduledTask::create($this->validTaskData(['project_id' => $project->id]));

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee('My Project')
            ->assertSee('1 task');
    }

    public function test_store_creates_project(): void
    {
        $this->post(route('projects.store'), ['name' => 'New Project', 'description' => 'A description'])
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', ['name' => 'New Project']);
    }

    public function test_store_validates_required_name(): void
    {
        $this->post(route('projects.store'), [])
            ->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_unique_name(): void
    {
        Project::create(['name' => 'Existing Project']);

        $this->post(route('projects.store'), ['name' => 'Existing Project'])
            ->assertSessionHasErrors(['name']);
    }

    public function test_update_renames_project(): void
    {
        $project = Project::create(['name' => 'Old Name']);

        $this->put(route('projects.update', $project), ['name' => 'New Name'])
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'New Name']);
    }

    public function test_update_allows_same_name_on_self(): void
    {
        $project = Project::create(['name' => 'My Project']);

        $this->put(route('projects.update', $project), ['name' => 'My Project', 'description' => 'Updated desc'])
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'description' => 'Updated desc']);
    }

    public function test_destroy_deletes_project_and_nullifies_task_project_ids(): void
    {
        $project = Project::create(['name' => 'Doomed Project']);
        $task = ScheduledTask::create($this->validTaskData(['project_id' => $project->id]));

        $this->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('scheduled_tasks', ['id' => $task->id, 'project_id' => null]);
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
