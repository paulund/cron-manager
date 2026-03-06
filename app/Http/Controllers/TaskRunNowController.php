<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RunTaskAction;
use App\Models\ScheduledTask;
use Illuminate\Http\RedirectResponse;

final class TaskRunNowController extends Controller
{
    public function __invoke(ScheduledTask $task, RunTaskAction $action): RedirectResponse
    {
        if ($task->isPaused()) {
            return to_route('tasks.show', $task)
                ->with('error', 'Task is snoozed until '.($task->paused_until?->format('D d M Y H:i') ?? '—').'. Edit the task to clear the snooze.');
        }

        if ($task->prevent_overlap && $task->taskRuns()->where('status', 'running')->exists()) {
            return to_route('tasks.show', $task)
                ->with('error', 'Task is already running. Overlap prevention is enabled.');
        }

        $run = $action($task);

        $message = $run->status === 'success'
            ? 'Task ran successfully.'
            : 'Task failed. Check run history for output.';

        $flashKey = $run->status === 'success' ? 'success' : 'error';

        return to_route('tasks.show', $task)->with($flashKey, $message);
    }
}
