<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Scheduler;

use App\Services\Scheduler\LinuxSchedulerInstaller;
use App\Services\Scheduler\MacOsSchedulerInstaller;
use App\Services\Scheduler\SchedulerInstallerFactory;
use App\Services\Scheduler\WindowsSchedulerInstaller;
use PHPUnit\Framework\TestCase;

class SchedulerInstallerFactoryTest extends TestCase
{
    private SchedulerInstallerFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new SchedulerInstallerFactory();
    }

    public function test_returns_macos_installer_for_darwin(): void
    {
        $installer = $this->factory->make('Darwin');

        $this->assertInstanceOf(MacOsSchedulerInstaller::class, $installer);
    }

    public function test_returns_linux_installer_for_linux(): void
    {
        $installer = $this->factory->make('Linux');

        $this->assertInstanceOf(LinuxSchedulerInstaller::class, $installer);
    }

    public function test_returns_windows_installer_for_windows(): void
    {
        $installer = $this->factory->make('Windows');

        $this->assertInstanceOf(WindowsSchedulerInstaller::class, $installer);
    }

    public function test_returns_linux_installer_for_unknown_os(): void
    {
        $installer = $this->factory->make('BSD');

        $this->assertInstanceOf(LinuxSchedulerInstaller::class, $installer);
    }
}
