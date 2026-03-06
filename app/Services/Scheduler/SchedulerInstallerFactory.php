<?php

declare(strict_types=1);

namespace App\Services\Scheduler;

class SchedulerInstallerFactory
{
    public function make(string $osFamily = PHP_OS_FAMILY): SchedulerInstallerInterface
    {
        return match ($osFamily) {
            'Darwin' => new MacOsSchedulerInstaller(),
            'Windows' => new WindowsSchedulerInstaller(),
            default => new LinuxSchedulerInstaller(),
        };
    }
}
