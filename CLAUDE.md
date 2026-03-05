# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Purpose

A local web application for managing cron jobs on the host machine. The system crontab contains one entry — `* * * * * php artisan schedule:run` — and this app provides a UI to add, edit, and delete the Laravel scheduled tasks that the scheduler runs. Tasks are stored in the database rather than hardcoded in PHP.

## Commands

```bash
composer dev          # Start all dev services (server, queue, logs, vite) concurrently
composer test         # Run PHPUnit test suite
composer setup        # Fresh install: dependencies, .env, key, migrate, npm build

php artisan serve     # Web server only (http://localhost:8000)
php artisan migrate   # Run pending migrations
php artisan tinker    # REPL

npm run dev           # Vite dev server (asset hot-reload)
npm run build         # Build production assets

./vendor/bin/pint     # Fix code style (Laravel Pint / PSR-12)
php artisan test --filter TestClassName   # Run a single test class
php artisan test --filter test_method_name  # Run a single test method
```

## Stack

- **Laravel 12** (PHP 8.4) with SQLite database (`database/database.sqlite`)
- **Laravel 12 app structure**: no `app/Console/Kernel.php` — schedule is defined in `routes/console.php` and/or `AppServiceProvider`
- No authentication (local-only tool)

## Planned Architecture

### Core concept
Scheduled tasks are stored in a `scheduled_tasks` database table. At boot time, a service provider reads all enabled tasks from the database and registers them with the Laravel scheduler dynamically — so `php artisan schedule:run` (triggered every minute by cron) picks them up.

### Key components to build

- **`scheduled_tasks` migration** — columns: `id`, `name`, `command` (artisan command or shell), `cron_expression`, `description`, `is_enabled`, `last_run_at`, `timestamps`
- **`ScheduledTask` model** — `app/Models/ScheduledTask.php`
- **`ScheduledTaskServiceProvider`** (or logic in `AppServiceProvider`) — queries enabled tasks on boot and registers each with `$schedule->command()` or `$schedule->exec()` using the stored cron expression
- **Web UI** — CRUD interface at `/` for managing tasks (Blade + Livewire or plain Blade with standard form requests)
- **Routes** — defined in `routes/web.php`, resource routes for tasks

### Scheduling registration pattern
In `routes/console.php` (Laravel 12 style):
```php
use Illuminate\Support\Facades\Schedule;
use App\Models\ScheduledTask;

// Load DB tasks — guard against missing table during initial migration
if (Schema::hasTable('scheduled_tasks')) {
    ScheduledTask::where('is_enabled', true)->each(function ($task) {
        Schedule::command($task->command)->cron($task->cron_expression);
    });
}
```
