<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ScheduledTask;
use App\Models\TaskRun;
use Illuminate\Support\Facades\Process;

final class RunTaskAction
{
    public function __construct(
        private readonly NotifyTaskFailureAction $notifier,
    ) {}

    public function __invoke(ScheduledTask $task): TaskRun
    {
        $run = TaskRun::create([
            'scheduled_task_id' => $task->id,
            'started_at' => now(),
            'status' => 'running',
            'triggered_by' => 'manual',
        ]);

        $extraPath = env('EXTRA_PATH', '');
        $currentPath = getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin';
        $path = $extraPath ? $extraPath.':'.$currentPath : $currentPath;

        $result = Process::path($task->working_directory ?? base_path())
            ->env([
                'PATH'    => $path,
                'HOME'    => getenv('HOME') ?: ($_SERVER['HOME'] ?? ''),
                'USER'    => getenv('USER') ?: ($_SERVER['USER'] ?? ''),
                'LOGNAME' => getenv('LOGNAME') ?: ($_SERVER['LOGNAME'] ?? ''),
                'SHELL'   => getenv('SHELL') ?: ($_SERVER['SHELL'] ?? '/bin/zsh'),
            ])
            ->timeout(300)
            ->run($task->command);

        $run->update([
            'finished_at' => now(),
            'output' => $result->output() ?: $result->errorOutput(),
            'exit_code' => $result->exitCode(),
            'status' => $result->successful() ? 'success' : 'failed',
        ]);

        $task->update(['last_run_at' => now()]);

        $run = $run->fresh();

        if ($run->status === 'failed') {
            ($this->notifier)($task, $run);
        }

        $task->pruneHistory();

        return $run;
    }
}
