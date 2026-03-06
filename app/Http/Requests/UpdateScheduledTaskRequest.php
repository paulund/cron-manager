<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateScheduledTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'command_type' => ['required', 'in:shell,claude,copilot'],
            'command' => ['required', 'string', 'max:2048'],
            'working_directory' => ['nullable', 'string', 'max:512'],
            'cron_expression' => ['required', 'string', 'max:100', $this->validCronRule()],
            'is_enabled' => ['boolean'],
            'prevent_overlap' => ['boolean'],
            'runs_to_keep' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'notify_on_failure' => ['boolean'],
            'notification_email' => ['nullable', 'email', 'max:255'],
            'notification_webhook' => ['nullable', 'url', 'max:512'],
            'env_vars' => ['nullable', 'array'],
            'env_vars.*.key' => ['required_with:env_vars.*', 'string', 'max:128', 'regex:/^[A-Z_][A-Z0-9_]*$/i'],
            'env_vars.*.value' => ['nullable', 'string', 'max:1024'],
            'depends_on_task_id' => ['nullable', 'integer', 'exists:scheduled_tasks,id'],
            'paused_until' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
        ];
    }

    private function validCronRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            try {
                new \Cron\CronExpression($value);
            } catch (\InvalidArgumentException) {
                $fail('The cron expression is invalid.');
            }
        };
    }
}
