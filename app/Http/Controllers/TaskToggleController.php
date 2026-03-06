<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ScheduledTask;
use Illuminate\Http\RedirectResponse;

final class TaskToggleController extends Controller
{
    public function __invoke(ScheduledTask $task): RedirectResponse
    {
        $task->update(['is_enabled' => ! $task->is_enabled]);

        $state = $task->is_enabled ? 'enabled' : 'disabled';

        return back()->with('success', "Task {$state}.");
    }
}
