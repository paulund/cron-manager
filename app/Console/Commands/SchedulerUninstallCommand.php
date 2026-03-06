<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Scheduler\SchedulerInstallerFactory;
use Illuminate\Console\Command;

final class SchedulerUninstallCommand extends Command
{
    /** @var string */
    protected $signature = 'scheduler:uninstall';

    /** @var string */
    protected $description = 'Remove the platform-appropriate scheduler trigger';

    public function __construct(private readonly SchedulerInstallerFactory $factory)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $installer = $this->factory->make();

        if (! $installer->isInstalled()) {
            $this->warn('No scheduler trigger is currently installed.');

            return self::SUCCESS;
        }

        $installer->uninstall();

        $this->info('Scheduler uninstalled successfully.');

        return self::SUCCESS;
    }
}
