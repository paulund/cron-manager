<?php

declare(strict_types=1);

namespace App\Services\Scheduler;

final class WindowsSchedulerInstaller implements SchedulerInstallerInterface
{
    private const TASK_NAME = 'CronManagerScheduler';

    private const TASK_FOLDER = '\CronManager';

    private function taskXml(?string $phpBinaryPath = null, ?string $artisanPath = null): string
    {
        $phpBinaryPath ??= PHP_BINARY;
        $artisanPath ??= base_path('artisan');

        $phpBinary = htmlspecialchars($phpBinaryPath, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $artisan = htmlspecialchars($artisanPath, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <Task version="1.2" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task">
          <RegistrationInfo>
            <Description>Cron Manager Scheduler</Description>
          </RegistrationInfo>
          <Triggers>
            <TimeTrigger>
              <StartBoundary>2025-01-01T00:00:00</StartBoundary>
              <Enabled>true</Enabled>
              <Repetition>
                <Interval>PT1M</Interval>
                <Duration>P1D</Duration>
                <StopAtDurationEnd>false</StopAtDurationEnd>
              </Repetition>
            </TimeTrigger>
          </Triggers>
          <Principals>
            <Principal id="Author">
              <LogonType>InteractiveToken</LogonType>
              <RunLevel>LeastPrivilege</RunLevel>
            </Principal>
          </Principals>
          <Settings>
            <MultipleInstancesPolicy>IgnoreNew</MultipleInstancesPolicy>
            <DisallowStartIfOnBatteries>false</DisallowStartIfOnBatteries>
            <StopIfGoingOnBatteries>false</StopIfGoingOnBatteries>
            <StartWhenAvailable>true</StartWhenAvailable>
            <Enabled>true</Enabled>
            <Hidden>false</Hidden>
          </Settings>
          <Actions>
            <Exec>
              <Command>{$phpBinary}</Command>
              <Arguments>&quot;{$artisan}&quot; schedule:run</Arguments>
            </Exec>
          </Actions>
        </Task>
        XML;
    }

    private function xmlPath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::TASK_NAME . '.xml';
    }

    private function fullTaskName(): string
    {
        return self::TASK_FOLDER . '\\' . self::TASK_NAME;
    }

    public function install(): void
    {
        $xmlPath = $this->xmlPath();
        file_put_contents($xmlPath, $this->taskXml());

        exec('schtasks /create /xml ' . escapeshellarg($xmlPath) . ' /tn ' . escapeshellarg($this->fullTaskName()) . ' /f 2>&1', $output, $exitCode);

        unlink($xmlPath);

        if ($exitCode !== 0) {
            throw new \RuntimeException('schtasks /create failed: ' . implode("\n", $output));
        }
    }

    public function uninstall(): void
    {
        exec('schtasks /delete /tn ' . escapeshellarg($this->fullTaskName()) . ' /f 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException('schtasks /delete failed: ' . implode("\n", $output));
        }
    }

    public function isInstalled(): bool
    {
        $output = shell_exec('schtasks /query /tn ' . escapeshellarg($this->fullTaskName()) . ' 2>&1') ?? '';

        return str_contains($output, self::TASK_NAME);
    }

    public function isLoaded(): bool
    {
        return $this->isInstalled();
    }

    public function describe(): string
    {
        return 'Windows Task Scheduler task: ' . $this->fullTaskName();
    }

    public function postInstallMessage(): ?string
    {
        return null;
    }
}
