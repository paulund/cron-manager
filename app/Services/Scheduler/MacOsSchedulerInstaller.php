<?php

declare(strict_types=1);

namespace App\Services\Scheduler;

final class MacOsSchedulerInstaller implements SchedulerInstallerInterface
{
    private const LABEL = 'com.cron-manager.scheduler';

    private function plistPath(): string
    {
        $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? '~');

        return $home . '/Library/LaunchAgents/' . self::LABEL . '.plist';
    }

    private function plistContent(): string
    {
        $phpBinary = PHP_BINARY;
        $artisan = base_path('artisan');
        $logPath = storage_path('logs/scheduler.log');
        $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? '/tmp');
        $user = getenv('USER') ?: ($_SERVER['USER'] ?? '');
        // Prepend known user binary locations so launchd's minimal PATH finds tools like claude
        $basePath = '/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin';
        $path = "{$home}/.local/bin:/opt/homebrew/bin:{$basePath}";

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>{self::LABEL}</string>
    <key>ProgramArguments</key>
    <array>
        <string>{$phpBinary}</string>
        <string>{$artisan}</string>
        <string>schedule:run</string>
    </array>
    <key>EnvironmentVariables</key>
    <dict>
        <key>HOME</key>
        <string>{$home}</string>
        <key>USER</key>
        <string>{$user}</string>
        <key>LOGNAME</key>
        <string>{$user}</string>
        <key>PATH</key>
        <string>{$path}</string>
    </dict>
    <key>StartInterval</key>
    <integer>60</integer>
    <key>RunAtLoad</key>
    <false/>
    <key>StandardOutPath</key>
    <string>{$logPath}</string>
    <key>StandardErrorPath</key>
    <string>{$logPath}</string>
</dict>
</plist>
XML;
    }

    public function install(): void
    {
        $plistPath = $this->plistPath();
        $dir = dirname($plistPath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($plistPath, $this->plistContent());
        $this->launchctlLoad($plistPath);
    }

    public function uninstall(): void
    {
        $plistPath = $this->plistPath();

        if (file_exists($plistPath)) {
            $this->launchctlUnload($plistPath);
            unlink($plistPath);
        }
    }

    public function isInstalled(): bool
    {
        return file_exists($this->plistPath());
    }

    public function isLoaded(): bool
    {
        $uid = trim(shell_exec('id -u') ?: '');

        if ($uid !== '') {
            $output = shell_exec("launchctl print gui/{$uid}/" . self::LABEL . ' 2>/dev/null') ?? '';

            return str_contains($output, 'state =');
        }

        // Fallback for environments where id -u is unavailable
        $output = shell_exec('launchctl list ' . self::LABEL . ' 2>/dev/null') ?? '';

        return str_contains($output, self::LABEL);
    }

    private function launchctlLoad(string $plistPath): void
    {
        $uid = trim(shell_exec('id -u') ?: '');

        if ($uid !== '') {
            $result = shell_exec("launchctl bootstrap gui/{$uid} " . escapeshellarg($plistPath) . ' 2>&1') ?? '';

            if (! str_contains($result, 'error')) {
                return;
            }
        }

        // Fallback to legacy command
        shell_exec('launchctl load ' . escapeshellarg($plistPath) . ' 2>&1');
    }

    private function launchctlUnload(string $plistPath): void
    {
        $uid = trim(shell_exec('id -u') ?: '');

        if ($uid !== '') {
            $result = shell_exec("launchctl bootout gui/{$uid}/" . self::LABEL . ' 2>&1') ?? '';

            if (! str_contains($result, 'error')) {
                return;
            }
        }

        // Fallback to legacy command
        shell_exec('launchctl unload ' . escapeshellarg($plistPath) . ' 2>&1');
    }

    public function describe(): string
    {
        return 'LaunchAgent at ~/Library/LaunchAgents/' . self::LABEL . '.plist';
    }
}
