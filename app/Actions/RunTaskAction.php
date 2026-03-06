<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ScheduledTask;
use App\Models\TaskRun;
use Illuminate\Support\Facades\Process;

final readonly class RunTaskAction
{
    public function __construct(
        private NotifyTaskFailureAction $notifier,
    ) {}

    public function __invoke(ScheduledTask $task): TaskRun
    {
        $run = TaskRun::create([
            'scheduled_task_id' => $task->id,
            'started_at' => now(),
            'status' => 'running',
            'triggered_by' => 'manual',
        ]);

        $extraPath = getenv('EXTRA_PATH') ?: '';
        $currentPath = getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin';
        $path = $extraPath !== '' && $extraPath !== '0' ? $extraPath.':'.$currentPath : $currentPath;

        $shell = getenv('SHELL') ?: (\Illuminate\Support\Facades\Request::server('SHELL') ?? '');

        $env = [
            'PATH' => $path,
            'HOME' => getenv('HOME') ?: (\Illuminate\Support\Facades\Request::server('HOME') ?? ''),
            'USER' => getenv('USER') ?: (\Illuminate\Support\Facades\Request::server('USER') ?? ''),
            'LOGNAME' => getenv('LOGNAME') ?: (\Illuminate\Support\Facades\Request::server('LOGNAME') ?? ''),
        ];

        if ($shell !== '') {
            $env['SHELL'] = $shell;
        }

        $result = Process::path($task->working_directory ?? base_path())
            ->env($env)
            ->timeout(300)
            ->run($task->buildCommand());

        $run->update([
            'finished_at' => now(),
            'output' => $result->output() ?: $result->errorOutput(),
            'exit_code' => $result->exitCode(),
            'status' => $result->successful() ? 'success' : 'failed',
        ]);

        $task->update(['last_run_at' => now()]);

        $run = $run->fresh() ?? $run;

        if ($run->status === 'failed') {
            ($this->notifier)($task, $run);
        }

        $task->pruneHistory();

        return $run;
    }
}
