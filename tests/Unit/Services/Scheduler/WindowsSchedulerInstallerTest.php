<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Scheduler;

use App\Services\Scheduler\WindowsSchedulerInstaller;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class WindowsSchedulerInstallerTest extends TestCase
{
    public function test_describe_contains_task_name(): void
    {
        $installer = new WindowsSchedulerInstaller;

        $description = $installer->describe();

        $this->assertStringContainsString('CronManagerScheduler', $description);
        $this->assertStringContainsString('CronManager', $description);
    }

    public function test_is_loaded_matches_is_installed(): void
    {
        $installer = new WindowsSchedulerInstaller;

        $this->assertSame($installer->isInstalled(), $installer->isLoaded());
    }

    public function test_task_xml_quotes_artisan_path_in_arguments(): void
    {
        $installer = new WindowsSchedulerInstaller;
        $method = new ReflectionMethod($installer, 'taskXml');

        $xml = $method->invoke($installer, 'C:\\php\\php.exe', 'C:\\Projects\\my app\\artisan');

        $this->assertStringContainsString('<Arguments>&quot;C:\Projects\my app\artisan&quot; schedule:run</Arguments>', $xml);
    }

    public function test_task_xml_escapes_xml_special_characters_in_paths(): void
    {
        $installer = new WindowsSchedulerInstaller;
        $method = new ReflectionMethod($installer, 'taskXml');

        $xml = $method->invoke($installer, 'C:\\php\\php&8.exe', 'C:\\Projects\\app<1>\\artisan');

        $this->assertStringContainsString('<Command>C:\php\php&amp;8.exe</Command>', $xml);
        $this->assertStringContainsString('<Arguments>&quot;C:\Projects\app&lt;1&gt;\artisan&quot; schedule:run</Arguments>', $xml);
    }

    public function test_task_xml_is_valid_xml_with_spaces_in_paths(): void
    {
        $installer = new WindowsSchedulerInstaller;
        $method = new ReflectionMethod($installer, 'taskXml');

        $xml = $method->invoke($installer, 'C:\\Program Files\\PHP\\php.exe', 'C:\\Users\\My Name\\Projects\\cron-manager\\artisan');

        $dom = new \DOMDocument;
        $this->assertTrue($dom->loadXML(trim($xml)), 'Generated XML must be valid when paths contain spaces');
    }
}
