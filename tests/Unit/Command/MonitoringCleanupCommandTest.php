<?php

namespace App\Tests\Unit\Command;

use App\Command\MonitoringCleanupCommand;
use App\Service\MonitoringService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MonitoringCleanupCommandTest extends TestCase
{
    private MockObject $monitoringService;
    private MonitoringCleanupCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->monitoringService = $this->createMock(MonitoringService::class);
        $this->command = new MonitoringCleanupCommand($this->monitoringService);
        
        $application = new Application();
        $application->add($this->command);
        
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithDefaultOptions(): void
    {
        $this->monitoringService
            ->expects($this->once())
            ->method('cleanupOldMetrics')
            ->with(24);

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Successfully cleaned up', $this->commandTester->getDisplay());
    }

    public function testExecuteWithCustomHours(): void
    {
        $this->monitoringService
            ->expects($this->once())
            ->method('cleanupOldMetrics')
            ->with(48);

        $this->commandTester->execute(['--older-than-hours' => '48']);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('older than 48 hours', $this->commandTester->getDisplay());
    }

    public function testDryRunDoesNotCallCleanup(): void
    {
        $this->monitoringService
            ->expects($this->never())
            ->method('cleanupOldMetrics');

        $this->commandTester->execute(['--dry-run' => true]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
        $this->assertStringContainsString('would be deleted', $this->commandTester->getDisplay());
    }

    public function testInvalidHoursReturnsError(): void
    {
        $this->monitoringService
            ->expects($this->never())
            ->method('cleanupOldMetrics');

        $this->commandTester->execute(['--older-than-hours' => '0']);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('must be at least 1', $this->commandTester->getDisplay());
    }

    public function testWarningForRecentMetrics(): void
    {
        $this->monitoringService
            ->expects($this->never())
            ->method('cleanupOldMetrics');

        // Simulate user answering 'no' to the confirmation
        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute(['--older-than-hours' => '12']);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('not recommended', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Operation cancelled', $this->commandTester->getDisplay());
    }

    public function testProceedWithWarning(): void
    {
        $this->monitoringService
            ->expects($this->once())
            ->method('cleanupOldMetrics')
            ->with(12);

        // Simulate user answering 'yes' to the confirmation
        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute(['--older-than-hours' => '12']);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('not recommended', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Successfully cleaned up', $this->commandTester->getDisplay());
    }

    public function testServiceExceptionHandling(): void
    {
        $this->monitoringService
            ->expects($this->once())
            ->method('cleanupOldMetrics')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->commandTester->execute([]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Failed to clean up', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Database connection failed', $this->commandTester->getDisplay());
    }

    public function testShowsRecommendations(): void
    {
        $this->monitoringService
            ->expects($this->once())
            ->method('cleanupOldMetrics')
            ->with(24);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Recommendations', $output);
        $this->assertStringContainsString('cron job', $output);
        $this->assertStringContainsString('0 2 * * *', $output);
        $this->assertStringContainsString('/path/to/php', $output);
    }

    public function testTimeFormatting(): void
    {
        // Test time formats in the dry run output - use longer periods to avoid warnings
        $this->commandTester->execute(['--dry-run' => true, '--older-than-hours' => '24']);
        $this->assertStringContainsString('1.0 day ago', $this->commandTester->getDisplay());

        $this->commandTester->execute(['--dry-run' => true, '--older-than-hours' => '48']);
        $this->assertStringContainsString('2.0 days ago', $this->commandTester->getDisplay());

        $this->commandTester->execute(['--dry-run' => true, '--older-than-hours' => '72']);
        $this->assertStringContainsString('3.0 days ago', $this->commandTester->getDisplay());
    }
}