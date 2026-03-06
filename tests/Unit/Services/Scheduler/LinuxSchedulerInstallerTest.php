<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Scheduler;

use App\Services\Scheduler\LinuxSchedulerInstaller;
use Tests\TestCase;

class LinuxSchedulerInstallerTest extends TestCase
{
    public function test_describe_contains_systemd_unit_name(): void
    {
        $installer = new LinuxSchedulerInstaller;

        $description = $installer->describe();

        $this->assertStringContainsString('systemd', $description);
        $this->assertStringContainsString('cron-manager-scheduler', $description);
    }

    public function test_is_installed_returns_bool(): void
    {
        $installer = new LinuxSchedulerInstaller;

        $this->assertIsBool($installer->isInstalled());
    }

    public function test_is_loaded_returns_bool(): void
    {
        $installer = new LinuxSchedulerInstaller;

        $this->assertIsBool($installer->isLoaded());
    }

    public function test_exec_start_paths_are_double_quoted(): void
    {
        $installer = new LinuxSchedulerInstaller;

        $content = $this->getServiceContent($installer);

        // ExecStart must wrap each argument in double-quotes so systemd handles
        // paths with spaces or special characters correctly.
        $this->assertMatchesRegularExpression(
            '/^ExecStart="[^"]*" "[^"]*" schedule:run$/m',
            $content
        );
    }

    public function test_exec_start_escapes_backslashes_and_quotes_in_paths(): void
    {
        $installer = new LinuxSchedulerInstaller;

        $method = new \ReflectionMethod($installer, 'escapeExecArg');

        $this->assertSame('"/path/to/php"', $method->invoke($installer, '/path/to/php'));
        $this->assertSame('"/path with spaces/php"', $method->invoke($installer, '/path with spaces/php'));
        $this->assertSame('"/path/with\\"quote/php"', $method->invoke($installer, '/path/with"quote/php'));
        $this->assertSame('"/path/with\\\\backslash/php"', $method->invoke($installer, '/path/with\\backslash/php'));
    }

    /**
     * Access the private serviceContent() method via reflection for testing.
     */
    private function getServiceContent(LinuxSchedulerInstaller $installer): string
    {
        $method = new \ReflectionMethod($installer, 'serviceContent');

        return $method->invoke($installer);
    }
}
