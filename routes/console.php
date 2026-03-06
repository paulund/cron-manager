<?php

declare(strict_types=1);

use App\Actions\NotifyTaskFailureAction;
use App\Models\ScheduledTask;
use App\Models\TaskRun;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

// Heartbeat: touch a file every minute so the UI can show scheduler health
Schedule::call(fn (): bool => touch(storage_path('app/scheduler-heartbeat')))->everyMinute();

if (Schema::hasTable('scheduled_tasks')) {
    ScheduledTask::enabled()->each(function (ScheduledTask $task): void {
        $runId = null;

        Schedule::exec($task->buildCommand())
            ->cron($task->cron_expression)
            ->before(function () use ($task, &$runId): void {
                // Skip if snoozed
                if ($task->isPaused()) {
                    return;
                }

                // Overlap protection: skip if a run is still in progress
                if ($task->prevent_overlap && $task->taskRuns()->where('status', 'running')->exists()) {
                    return;
                }

                // Dependency check: skip if dependency did not succeed in the last run
                if ($task->depends_on_task_id !== null) {
                    $depLatest = $task->dependsOn?->taskRuns()
                        ->latest('started_at')
                        ->value('status');

                    if ($depLatest !== 'success') {
                        return;
                    }
                }

                $run = TaskRun::create([
                    'scheduled_task_id' => $task->id,
                    'started_at' => now(),
                    'status' => 'running',
                    'triggered_by' => 'scheduler',
                ]);
                $runId = $run->id;
                $task->update(['last_run_at' => now()]);
            })
            ->thenWithOutput(function (string $output) use ($task, &$runId): void {
                if ($runId === null) {
                    return;
                }

                TaskRun::find($runId)?->update([
                    'finished_at' => now(),
                    'output' => $output,
                    'status' => 'success',
                    'exit_code' => 0,
                ]);

                $task->pruneHistory();
            })
            ->onFailureWithOutput(function (string $output) use ($task, &$runId): void {
                if ($runId === null) {
                    return;
                }

                $run = TaskRun::find($runId);
                $run?->update([
                    'finished_at' => now(),
                    'output' => $output,
                    'status' => 'failed',
                    'exit_code' => 1,
                ]);

                if ($run) {
                    resolve(NotifyTaskFailureAction::class)($task, $run->fresh() ?? $run);
                }

                $task->pruneHistory();
            });
    });
}
