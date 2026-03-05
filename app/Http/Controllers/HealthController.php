<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;

final class HealthController extends Controller
{
    public function __invoke(): View
    {
        $heartbeatPath = storage_path('app/scheduler-heartbeat');
        $heartbeatExists = file_exists($heartbeatPath);
        $lastSeen = $heartbeatExists ? Carbon::createFromTimestamp(filemtime($heartbeatPath)) : null;
        $ageSeconds = $lastSeen ? (int) now()->diffInSeconds($lastSeen) : null;

        $status = match (true) {
            ! $heartbeatExists => 'never',
            $ageSeconds <= 90 => 'healthy',
            $ageSeconds <= 300 => 'warning',
            default => 'stale',
        };

        $crontabOutput = shell_exec('crontab -l 2>/dev/null') ?? '';
        $cronInstalled = str_contains($crontabOutput, 'schedule:run')
            && str_contains($crontabOutput, base_path());

        $phpBinary = PHP_BINARY;
        $projectPath = base_path();
        $cronEntry = "* * * * * cd {$projectPath} && {$phpBinary} artisan schedule:run >> /dev/null 2>&1";

        return view('health.index', compact('status', 'lastSeen', 'ageSeconds', 'cronInstalled', 'cronEntry'));
    }
}
