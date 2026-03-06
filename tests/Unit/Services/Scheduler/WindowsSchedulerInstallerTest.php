<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Scheduler;

use App\Services\Scheduler\WindowsSchedulerInstaller;
use PHPUnit\Framework\TestCase;

class WindowsSchedulerInstallerTest extends TestCase
{
    public function test_describe_contains_task_name(): void
    {
        $installer = new WindowsSchedulerInstaller();

        $description = $installer->describe();

        $this->assertStringContainsString('CronManagerScheduler', $description);
        $this->assertStringContainsString('CronManager', $description);
    }

    public function test_is_loaded_matches_is_installed(): void
    {
        $installer = new WindowsSchedulerInstaller();

        $this->assertSame($installer->isInstalled(), $installer->isLoaded());
    }
}
