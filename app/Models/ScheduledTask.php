<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScheduledTask extends Model
{
    protected $fillable = [
        'project_id', 'name', 'description', 'command_type', 'command',
        'working_directory', 'env_vars', 'cron_expression', 'is_enabled', 'last_run_at',
        'prevent_overlap', 'runs_to_keep', 'notify_on_failure',
        'notification_email', 'notification_webhook',
        'depends_on_task_id', 'paused_until',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'prevent_overlap' => 'boolean',
        'notify_on_failure' => 'boolean',
        'runs_to_keep' => 'integer',
        'env_vars' => 'array',
        'last_run_at' => 'immutable_datetime',
        'paused_until' => 'immutable_datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(ScheduledTask::class, 'depends_on_task_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(ScheduledTask::class, 'depends_on_task_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'scheduled_task_tag');
    }

    public function taskRuns(): HasMany
    {
        return $this->hasMany(TaskRun::class);
    }

    public function latestRun(): HasOne
    {
        return $this->hasOne(TaskRun::class)->latestOfMany('started_at');
    }

    public function scopeEnabled(Builder $query): void
    {
        $query->where('is_enabled', true);
    }

    public function nextRunAt(): ?\DateTimeInterface
    {
        try {
            return (new \Cron\CronExpression($this->cron_expression))->getNextRunDate();
        } catch (\Exception) {
            return null;
        }
    }

    public function buildCommand(): string
    {
        $envPrefix = '';

        if (! empty($this->env_vars)) {
            $pairs = array_map(
                fn (array $pair) => escapeshellarg($pair['key']).'='.escapeshellarg($pair['value']),
                array_filter($this->env_vars, fn ($p) => ! empty($p['key']))
            );

            if ($pairs !== []) {
                $envPrefix = implode(' ', $pairs).' ';
            }
        }

        $command = $envPrefix.$this->command;

        if ($this->working_directory) {
            $command = 'cd '.escapeshellarg($this->working_directory).' && '.$command;
        }

        // Wrap claude commands in a login shell so the subprocess inherits the
        // user's full environment (PATH, HOME, credentials) without needing an API key.
        if ($this->command_type === 'claude') {
            return '/bin/zsh -l -c '.escapeshellarg($command);
        }

        return $command;
    }

    public function isPaused(): bool
    {
        return $this->paused_until !== null && $this->paused_until->isFuture();
    }

    public function pruneHistory(): void
    {
        if ($this->runs_to_keep === null) {
            return;
        }

        $keepIds = $this->taskRuns()
            ->latest('started_at')
            ->limit($this->runs_to_keep)
            ->pluck('id');

        $this->taskRuns()
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
