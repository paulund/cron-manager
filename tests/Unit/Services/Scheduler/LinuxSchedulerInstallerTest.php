<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Scheduler;

use App\Services\Scheduler\LinuxSchedulerInstaller;
use PHPUnit\Framework\TestCase;

class LinuxSchedulerInstallerTest extends TestCase
{
    public function test_describe_contains_systemd_unit_name(): void
    {
        $installer = new LinuxSchedulerInstaller();

        $description = $installer->describe();

        $this->assertStringContainsString('systemd', $description);
        $this->assertStringContainsString('cron-manager-scheduler', $description);
    }

    public function test_is_installed_returns_bool(): void
    {
        $installer = new LinuxSchedulerInstaller();

        $this->assertIsBool($installer->isInstalled());
    }

    public function test_is_loaded_returns_bool(): void
    {
        $installer = new LinuxSchedulerInstaller();

        $this->assertIsBool($installer->isLoaded());
    }
}
