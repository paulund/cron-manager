<?php

declare(strict_types=1);

namespace App\Services\Scheduler;

interface SchedulerInstallerInterface
{
    public function install(): void;

    public function uninstall(): void;

    public function isInstalled(): bool;

    public function isLoaded(): bool;

    public function describe(): string;
}
