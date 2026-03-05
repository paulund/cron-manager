<?php

declare(strict_types=1);

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CronPreviewController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ScheduledTaskController;
use App\Http\Controllers\TaskRunNowController;
use App\Http\Controllers\TaskToggleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tasks');

Route::resource('projects', ProjectController::class)->except('show');
Route::resource('tasks', ScheduledTaskController::class);
Route::post('tasks/{task}/run', TaskRunNowController::class)->name('tasks.run');
Route::post('tasks/{task}/toggle', TaskToggleController::class)->name('tasks.toggle');
Route::get('cron-preview', CronPreviewController::class)->name('cron.preview');
Route::get('calendar', CalendarController::class)->name('calendar');
Route::get('health', HealthController::class)->name('health');
