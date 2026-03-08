<?php

declare(strict_types=1);

namespace App\Services\Scheduler;

final class LinuxSchedulerInstaller implements SchedulerInstallerInterface
{
    private const UNIT_NAME = 'cron-manager-scheduler';

    private function systemdDir(): string
    {
        return (getenv('HOME') ?: '~').'/.config/systemd/user';
    }

    private function servicePath(): string
    {
        return $this->systemdDir().'/'.self::UNIT_NAME.'.service';
    }

    private function timerPath(): string
    {
        return $this->systemdDir().'/'.self::UNIT_NAME.'.timer';
    }

    /**
     * Escape a path for use as a systemd ExecStart argument.
     * Wraps the value in double quotes and escapes backslashes and double quotes
     * using systemd's C-style string escaping, so paths with spaces or special
     * characters are parsed correctly by systemd.
     */
    private function escapeExecArg(string $path): string
    {
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $path);

        return '"'.$escaped.'"';
    }

    private function serviceContent(): string
    {
        $phpBinary = PHP_BINARY;
        $artisan = defined('ARTISAN_BINARY') ? ARTISAN_BINARY : dirname(__DIR__, 3).'/artisan';
        $home = getenv('HOME') ?: '/tmp';
        $user = getenv('USER') ?: '';
        // Prepend known user binary locations so systemd's minimal PATH finds tools like claude
        $basePath = '/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin';
        $path = "{$home}/.local/bin:{$basePath}";
        $execStart = $this->escapeExecArg($phpBinary).' '.$this->escapeExecArg($artisan).' schedule:run';

        return "[Unit]\nDescription=Cron Manager Scheduler\n\n[Service]\nType=oneshot\nEnvironment=HOME={$home}\nEnvironment=USER={$user}\nEnvironment=LOGNAME={$user}\nEnvironment=PATH={$path}\nExecStart={$execStart}\n";
    }

    private function timerContent(): string
    {
        return "[Unit]\nDescription=Timer for Cron Manager Scheduler\n\n[Timer]\nOnCalendar=*-*-* *:*:00\nPersistent=true\n\n[Install]\nWantedBy=timers.target\n";
    }

    public function install(): void
    {
        $dir = $this->systemdDir();

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->servicePath(), $this->serviceContent());
        file_put_contents($this->timerPath(), $this->timerContent());

        exec('systemctl --user daemon-reload 2>&1');
        exec('systemctl --user enable --now '.escapeshellarg(self::UNIT_NAME.'.timer').' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Failed to enable systemd timer: '.implode("\n", $output));
        }
    }

    public function uninstall(): void
    {
        exec('systemctl --user disable --now '.escapeshellarg(self::UNIT_NAME.'.timer').' 2>/dev/null', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Failed to disable systemd timer: '.implode("\n", $output));
        }

        foreach ([$this->servicePath(), $this->timerPath()] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        exec('systemctl --user daemon-reload 2>&1');
    }

    public function isInstalled(): bool
    {
        return file_exists($this->servicePath()) && file_exists($this->timerPath());
    }

    public function isLoaded(): bool
    {
        $output = shell_exec('systemctl --user is-active '.escapeshellarg(self::UNIT_NAME.'.timer').' 2>/dev/null') ?? '';

        return trim($output) === 'active';
    }

    public function describe(): string
    {
        return 'systemd user timer: ~/.config/systemd/user/'.self::UNIT_NAME.'.timer';
    }

    public function postInstallMessage(): ?string
    {
        return null;
    }
}
