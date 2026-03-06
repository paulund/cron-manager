<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Scheduler\SchedulerInstallerFactory;
use Illuminate\Contracts\View\View;

final class HealthController extends Controller
{
    public function __construct(private readonly SchedulerInstallerFactory $factory) {}

    public function __invoke(): View
    {
        $heartbeatPath = storage_path('app/scheduler-heartbeat');
        $heartbeatExists = file_exists($heartbeatPath);
        $lastSeen = $heartbeatExists ? \Illuminate\Support\Facades\Date::createFromTimestamp((int) filemtime($heartbeatPath)) : null;
        $ageSeconds = $lastSeen instanceof \Carbon\Carbon ? (int) now()->diffInSeconds($lastSeen) : null;

        $status = match (true) {
            ! $heartbeatExists => 'never',
            $ageSeconds <= 90 => 'healthy',
            $ageSeconds <= 300 => 'warning',
            default => 'stale',
        };

        $crontabOutput = shell_exec('crontab -l 2>/dev/null') ?: '';
        $cronInstalled = str_contains($crontabOutput, 'schedule:run')
            && str_contains($crontabOutput, base_path());

        $phpBinary = PHP_BINARY;
        $projectPath = base_path();
        $cronEntry = "* * * * * cd {$projectPath} && {$phpBinary} artisan schedule:run >> /dev/null 2>&1";

        $installer = $this->factory->make();
        $schedulerInstalled = $installer->isInstalled();
        $schedulerLoaded = $schedulerInstalled && $installer->isLoaded();
        $installerDescription = $installer->describe();
        $installCommand = 'php artisan scheduler:install';

        return view('health.index', ['status' => $status, 'lastSeen' => $lastSeen, 'ageSeconds' => $ageSeconds, 'cronInstalled' => $cronInstalled, 'cronEntry' => $cronEntry, 'schedulerInstalled' => $schedulerInstalled, 'schedulerLoaded' => $schedulerLoaded, 'installerDescription' => $installerDescription, 'installCommand' => $installCommand]);
    }
}
