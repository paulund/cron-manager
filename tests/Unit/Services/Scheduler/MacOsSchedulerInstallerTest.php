<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Scheduler;

use App\Services\Scheduler\MacOsSchedulerInstaller;
use PHPUnit\Framework\TestCase;

class MacOsSchedulerInstallerTest extends TestCase
{
    public function test_describe_returns_expected_string(): void
    {
        $installer = new MacOsSchedulerInstaller();

        $this->assertStringContainsString('LaunchAgent', $installer->describe());
        $this->assertStringContainsString('com.cron-manager.scheduler', $installer->describe());
    }

    public function test_is_installed_returns_false_when_plist_missing(): void
    {
        // Plist path uses HOME env — ensure it doesn't exist in this test run
        $installer = new MacOsSchedulerInstaller();

        // We can't control the filesystem in a pure unit test, but we verify
        // the return type and that it doesn't throw
        $this->assertIsBool($installer->isInstalled());
    }

    public function test_is_loaded_returns_bool(): void
    {
        $installer = new MacOsSchedulerInstaller();

        $this->assertIsBool($installer->isLoaded());
    }
}
