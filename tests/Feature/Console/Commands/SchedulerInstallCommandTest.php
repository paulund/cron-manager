<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Services\Scheduler\SchedulerInstallerFactory;
use App\Services\Scheduler\SchedulerInstallerInterface;
use Mockery;
use Tests\TestCase;

class SchedulerInstallCommandTest extends TestCase
{

    public function test_install_command_shows_platform_and_description(): void
    {
        $installer = Mockery::mock(SchedulerInstallerInterface::class);
        $installer->shouldReceive('describe')->andReturn('Test description');
        $installer->shouldReceive('install')->once();

        $factory = Mockery::mock(SchedulerInstallerFactory::class);
        $factory->shouldReceive('make')->andReturn($installer);

        $this->app->instance(SchedulerInstallerFactory::class, $factory);

        $this->artisan('scheduler:install', ['--force' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Test description')
            ->expectsOutputToContain('Scheduler installed successfully');
    }

    public function test_install_command_with_confirmation_yes(): void
    {
        $installer = Mockery::mock(SchedulerInstallerInterface::class);
        $installer->shouldReceive('describe')->andReturn('Some installer');
        $installer->shouldReceive('install')->once();

        $factory = Mockery::mock(SchedulerInstallerFactory::class);
        $factory->shouldReceive('make')->andReturn($installer);

        $this->app->instance(SchedulerInstallerFactory::class, $factory);

        $this->artisan('scheduler:install')
            ->expectsConfirmation('Install scheduler?', 'yes')
            ->assertSuccessful();
    }

    public function test_install_command_aborts_on_no(): void
    {
        $installer = Mockery::mock(SchedulerInstallerInterface::class);
        $installer->shouldReceive('describe')->andReturn('Some installer');
        $installer->shouldNotReceive('install');

        $factory = Mockery::mock(SchedulerInstallerFactory::class);
        $factory->shouldReceive('make')->andReturn($installer);

        $this->app->instance(SchedulerInstallerFactory::class, $factory);

        $this->artisan('scheduler:install')
            ->expectsConfirmation('Install scheduler?', 'no')
            ->assertSuccessful()
            ->expectsOutputToContain('Aborted');
    }

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
