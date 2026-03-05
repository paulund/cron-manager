<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ScheduledTask;
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
        // Export HOME so the claude CLI can locate ~/.claude/ credentials in the cron environment
        $cronEntry = "* * * * * HOME=\$HOME cd {$projectPath} && {$phpBinary} artisan schedule:run >> /dev/null 2>&1";

        // Claude CLI readiness checks
        $claudeBinary = env('CLAUDE_BINARY', 'claude');
        $claudeBinaryResolved = trim((string) shell_exec('command -v '.escapeshellarg($claudeBinary).' 2>/dev/null'));
        $claudeBinaryFound = $claudeBinaryResolved !== '';
        $claudeCredsPath = ($_SERVER['HOME'] ?? getenv('HOME') ?: '') . '/.claude';
        $claudeCredsExist = is_dir($claudeCredsPath);
        $claudeTaskCount = ScheduledTask::where('command_type', 'claude')->count();

        return view('health.index', compact(
            'status', 'lastSeen', 'ageSeconds', 'cronInstalled', 'cronEntry',
            'claudeBinary', 'claudeBinaryFound', 'claudeBinaryResolved',
            'claudeCredsExist', 'claudeCredsPath', 'claudeTaskCount',
        ));
    }
}
