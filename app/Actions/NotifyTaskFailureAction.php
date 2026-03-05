<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ScheduledTask;
use App\Models\TaskRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class NotifyTaskFailureAction
{
    public function __invoke(ScheduledTask $task, TaskRun $run): void
    {
        if (! $task->notify_on_failure) {
            return;
        }

        $this->sendEmail($task, $run);
        $this->sendWebhook($task, $run);
    }

    private function sendEmail(ScheduledTask $task, TaskRun $run): void
    {
        if (! $task->notification_email) {
            return;
        }

        try {
            Mail::raw(
                $this->buildEmailBody($task, $run),
                function ($message) use ($task): void {
                    $message
                        ->to($task->notification_email)
                        ->subject("[Cron Manager] Task failed: {$task->name}");
                }
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send task failure email', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendWebhook(ScheduledTask $task, TaskRun $run): void
    {
        if (! $task->notification_webhook) {
            return;
        }

        try {
            Http::timeout(10)->post($task->notification_webhook, [
                'task' => $task->name,
                'task_id' => $task->id,
                'exit_code' => $run->exit_code,
                'output' => mb_substr($run->output ?? '', -2000),
                'started_at' => $run->started_at?->toIso8601String(),
                'finished_at' => $run->finished_at?->toIso8601String(),
                'triggered_by' => $run->triggered_by,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send task failure webhook', [
                'task_id' => $task->id,
                'url' => $task->notification_webhook,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildEmailBody(ScheduledTask $task, TaskRun $run): string
    {
        $lines = [
            "Task \"{$task->name}\" failed.",
            '',
            'Exit code: '.($run->exit_code ?? 'unknown'),
            'Started: '.($run->started_at?->format('Y-m-d H:i:s') ?? '—'),
            'Finished: '.($run->finished_at?->format('Y-m-d H:i:s') ?? '—'),
            'Triggered by: '.($run->triggered_by ?? '—'),
            '',
            'Command: '.$task->command,
        ];

        if ($run->output) {
            $lines[] = '';
            $lines[] = 'Output (last 2000 chars):';
            $lines[] = mb_substr($run->output, -2000);
        }

        return implode("\n", $lines);
    }
}
