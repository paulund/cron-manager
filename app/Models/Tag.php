<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Tag extends Model
{
    protected $fillable = ['name', 'color'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\ScheduledTask, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(ScheduledTask::class, 'scheduled_task_tag');
    }
}
