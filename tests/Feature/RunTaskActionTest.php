<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RunTaskAction;
use App\Models\ScheduledTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class RunTaskActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_run_creates_success_task_run(): void
    {
        Process::fake(['echo hello' => Process::result('hello', '', 0)]);

        $task = ScheduledTask::create([
            'name' => 'Echo Task',
            'command_type' => 'shell',
            'command' => 'echo hello',
            'cron_expression' => '* * * * *',
            'is_enabled' => true,
        ]);

        $run = resolve(RunTaskAction::class)($task);

        $this->assertSame('success', $run->status);
        $this->assertSame(0, $run->exit_code);
        $this->assertSame('manual', $run->triggered_by);
        $this->assertNotNull($run->finished_at);
    }

    public function test_failed_process_creates_failed_task_run(): void
    {
        Process::fake(['bad-command' => Process::result('', 'command not found', 127)]);

        $task = ScheduledTask::create([
            'name' => 'Bad Task',
            'command_type' => 'shell',
            'command' => 'bad-command',
            'cron_expression' => '* * * * *',
            'is_enabled' => true,
        ]);

        $run = resolve(RunTaskAction::class)($task);

        $this->assertSame('failed', $run->status);
        $this->assertSame(127, $run->exit_code);
        $this->assertStringContainsString('command not found', $run->output);
    }

    public function test_run_updates_last_run_at_on_task(): void
    {
        Process::fake();

        $task = ScheduledTask::create([
            'name' => 'Any Task',
            'command_type' => 'shell',
            'command' => 'echo hello',
            'cron_expression' => '* * * * *',
            'is_enabled' => true,
        ]);

        resolve(RunTaskAction::class)($task);

        $this->assertNotNull($task->fresh()->last_run_at);
    }
}
