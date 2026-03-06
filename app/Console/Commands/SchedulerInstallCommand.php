<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Scheduler\SchedulerInstallerFactory;
use Illuminate\Console\Command;

final class SchedulerInstallCommand extends Command
{
    /** @var string */
    protected $signature = 'scheduler:install {--force : Skip confirmation prompt}';

    /** @var string */
    protected $description = 'Install the platform-appropriate scheduler trigger';

    public function __construct(private readonly SchedulerInstallerFactory $factory)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $installer = $this->factory->make();

        $this->line('Detected platform: <info>' . PHP_OS_FAMILY . '</info>');
        $this->line('Will install: <info>' . $installer->describe() . '</info>');
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Install scheduler?', true)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $installer->install();

        $this->info('Scheduler installed successfully.');

        if (PHP_OS_FAMILY === 'Darwin') {
            $this->line('  The scheduler will run every minute while you are logged in.');
            $this->line('  You can remove your existing crontab entry if you added one manually.');
        }

        return self::SUCCESS;
    }
}
