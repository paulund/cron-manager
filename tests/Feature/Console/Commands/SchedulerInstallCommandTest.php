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
        $installer->shouldReceive('postInstallMessage')->andReturn(null);

        $factory = Mockery::mock(SchedulerInstallerFactory::class);
        $factory->shouldReceive('make')->andReturn($installer);

        $this->app->instance(SchedulerInstallerFactory::class, $factory);

        $this->artisan('scheduler:install', ['--force' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Test description')
            ->expectsOutputToContain('Scheduler installed successfully');
    }

    public function test_install_command_outputs_post_install_message(): void
    {
        $installer = Mockery::mock(SchedulerInstallerInterface::class);
        $installer->shouldReceive('describe')->andReturn('Some installer');
        $installer->shouldReceive('install')->once();
        $installer->shouldReceive('postInstallMessage')->andReturn("Line one.\nLine two.");

        $factory = Mockery::mock(SchedulerInstallerFactory::class);
        $factory->shouldReceive('make')->andReturn($installer);

        $this->app->instance(SchedulerInstallerFactory::class, $factory);

        $this->artisan('scheduler:install', ['--force' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Line one.')
            ->expectsOutputToContain('Line two.');
    }

    public function test_install_command_with_confirmation_yes(): void
    {
        $installer = Mockery::mock(SchedulerInstallerInterface::class);
        $installer->shouldReceive('describe')->andReturn('Some installer');
        $installer->shouldReceive('install')->once();
        $installer->shouldReceive('postInstallMessage')->andReturn(null);

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
        $installer->shouldNotReceive('postInstallMessage');

        $factory = Mockery::mock(SchedulerInstallerFactory::class);
        $factory->shouldReceive('make')->andReturn($installer);

        $this->app->instance(SchedulerInstallerFactory::class, $factory);

        $this->artisan('scheduler:install')
            ->expectsConfirmation('Install scheduler?', 'no')
            ->assertSuccessful()
            ->expectsOutputToContain('Aborted');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
