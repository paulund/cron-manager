<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Services\Scheduler\SchedulerInstallerFactory;
use App\Services\Scheduler\SchedulerInstallerInterface;
use Mockery;
use Tests\TestCase;

class SchedulerUninstallCommandTest extends TestCase
{
    public function test_uninstall_command_removes_installer_when_installed(): void
    {
        $installer = Mockery::mock(SchedulerInstallerInterface::class);
        $installer->shouldReceive('isInstalled')->andReturn(true);
        $installer->shouldReceive('uninstall')->once();

        $factory = Mockery::mock(SchedulerInstallerFactory::class);
        $factory->shouldReceive('make')->andReturn($installer);

        $this->app->instance(SchedulerInstallerFactory::class, $factory);

        $this->artisan('scheduler:uninstall')
            ->assertSuccessful()
            ->expectsOutputToContain('Scheduler uninstalled successfully');
    }

    public function test_uninstall_command_warns_when_not_installed(): void
    {
        $installer = Mockery::mock(SchedulerInstallerInterface::class);
        $installer->shouldReceive('isInstalled')->andReturn(false);
        $installer->shouldNotReceive('uninstall');

        $factory = Mockery::mock(SchedulerInstallerFactory::class);
        $factory->shouldReceive('make')->andReturn($installer);

        $this->app->instance(SchedulerInstallerFactory::class, $factory);

        $this->artisan('scheduler:uninstall')
            ->assertSuccessful()
            ->expectsOutputToContain('No scheduler trigger is currently installed');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
