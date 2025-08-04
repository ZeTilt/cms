<?php

namespace App\Command;

use App\Service\GalleryExpirationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:galleries:expiration',
    description: 'Manage gallery expiration automation (deactivate expired galleries, send reminders, cleanup)'
)]
class GalleryExpirationCommand extends Command
{
    public function __construct(
        private GalleryExpirationService $expirationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('deactivate', 'd', InputOption::VALUE_NONE, 'Deactivate expired galleries')
            ->addOption('reminders', 'r', InputOption::VALUE_NONE, 'Send expiration reminders')
            ->addOption('cleanup', 'c', InputOption::VALUE_NONE, 'Cleanup old expired galleries (archive)')
            ->addOption('stats', 's', InputOption::VALUE_NONE, 'Show expiration statistics')
            ->addOption('cleanup-days', null, InputOption::VALUE_OPTIONAL, 'Days before archiving expired galleries', 90)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be done without making changes')
            ->setHelp('
This command manages gallery expiration automation:

- Deactivate galleries that have expired
- Send reminder emails before expiration
- Archive old expired galleries
- Show statistics about gallery expiration

Examples:
  php bin/console zetilt:galleries:expiration --deactivate
  php bin/console zetilt:galleries:expiration --reminders
  php bin/console zetilt:galleries:expiration --cleanup --cleanup-days=60
  php bin/console zetilt:galleries:expiration --stats
  php bin/console zetilt:galleries:expiration --dry-run --deactivate

This command is designed to be run via cron job for automation.
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');

        $io->title('ZeTilt CMS - Gallery Expiration Management');

        if ($isDryRun) {
            $io->warning('DRY RUN MODE - No changes will be made');
        }

        // Show statistics
        if ($input->getOption('stats')) {
            return $this->showStats($io);
        }

        $hasAction = false;

        // Deactivate expired galleries
        if ($input->getOption('deactivate')) {
            $hasAction = true;
            $this->deactivateExpiredGalleries($io, $isDryRun);
        }

        // Send reminders
        if ($input->getOption('reminders')) {
            $hasAction = true;
            $this->sendReminders($io, $isDryRun);
        }

        // Cleanup old expired galleries
        if ($input->getOption('cleanup')) {
            $hasAction = true;
            $cleanupDays = (int) $input->getOption('cleanup-days');
            $this->cleanupOldGalleries($io, $cleanupDays, $isDryRun);
        }

        // If no specific action, run all actions
        if (!$hasAction) {
            $io->section('Running all expiration tasks');
            $this->deactivateExpiredGalleries($io, $isDryRun);
            $this->sendReminders($io, $isDryRun);
            $this->cleanupOldGalleries($io, 90, $isDryRun);
        }

        $io->success('Gallery expiration management completed successfully!');
        return Command::SUCCESS;
    }

    private function showStats(SymfonyStyle $io): int
    {
        $io->section('Gallery Expiration Statistics');

        try {
            $stats = $this->expirationService->getExpirationStats();

            $io->table(
                ['Metric', 'Count'],
                [
                    ['Total galleries with expiration', $stats['total_with_expiration']],
                    ['Currently expired', $stats['expired']],
                    ['Expiring in 1 day', $stats['expiring_1_day']],
                    ['Expiring in 7 days', $stats['expiring_7_days']],
                    ['Expiring in 30 days', $stats['expiring_30_days']],
                ]
            );

            $io->note('Last checked: ' . $stats['checked_at']);

            if ($stats['expired'] > 0) {
                $io->warning("⚠️  {$stats['expired']} galleries are currently expired and should be deactivated.");
            }

            if ($stats['expiring_1_day'] > 0) {
                $io->warning("⏰ {$stats['expiring_1_day']} galleries expire within 1 day.");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to get expiration statistics: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function deactivateExpiredGalleries(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Deactivating Expired Galleries');

        try {
            if ($isDryRun) {
                // Get expired galleries for dry run
                $expiredGalleries = $this->expirationService->getExpirationStats();
                $io->text("Would deactivate {$expiredGalleries['expired']} expired galleries");
                return;
            }

            $results = $this->expirationService->deactivateExpiredGalleries();

            $io->text("Processed: {$results['processed']} galleries");
            $io->text("Deactivated: {$results['deactivated']} galleries");

            if (!empty($results['errors'])) {
                $io->warning('Errors occurred:');
                foreach ($results['errors'] as $error) {
                    $io->text("  - {$error}");
                }
            }

            if ($results['deactivated'] > 0) {
                $io->success("✅ {$results['deactivated']} expired galleries have been deactivated.");
            } else {
                $io->note('No galleries needed to be deactivated.');
            }

        } catch (\Exception $e) {
            $io->error('Failed to deactivate expired galleries: ' . $e->getMessage());
        }
    }

    private function sendReminders(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Sending Expiration Reminders');

        try {
            if ($isDryRun) {
                $expiring7 = $this->expirationService->getGalleriesExpiringWithin(7);
                $expiring1 = $this->expirationService->getGalleriesExpiringWithin(1);
                $io->text("Would send reminders for " . (count($expiring7) + count($expiring1)) . " galleries");
                return;
            }

            $results = $this->expirationService->sendExpirationReminders();

            $io->text("Reminders sent: {$results['reminders_sent']}");

            if (!empty($results['errors'])) {
                $io->warning('Errors occurred:');
                foreach ($results['errors'] as $error) {
                    $io->text("  - {$error}");
                }
            }

            if ($results['reminders_sent'] > 0) {
                $io->success("✅ {$results['reminders_sent']} expiration reminders have been sent.");
            } else {
                $io->note('No reminders needed to be sent.');
            }

        } catch (\Exception $e) {
            $io->error('Failed to send expiration reminders: ' . $e->getMessage());
        }
    }

    private function cleanupOldGalleries(SymfonyStyle $io, int $cleanupDays, bool $isDryRun): void
    {
        $io->section("Cleaning Up Old Expired Galleries (older than {$cleanupDays} days)");

        try {
            if ($isDryRun) {
                $cutoffDate = new \DateTimeImmutable("-{$cleanupDays} days");
                $io->text("Would archive galleries expired before: " . $cutoffDate->format('Y-m-d'));
                return;
            }

            $results = $this->expirationService->cleanupOldExpiredGalleries($cleanupDays);

            $io->text("Processed: {$results['processed']} old expired galleries");
            $io->text("Archived: {$results['archived']} galleries");

            if (!empty($results['errors'])) {
                $io->warning('Errors occurred:');
                foreach ($results['errors'] as $error) {
                    $io->text("  - {$error}");
                }
            }

            if ($results['archived'] > 0) {
                $io->success("✅ {$results['archived']} old expired galleries have been archived.");
            } else {
                $io->note('No old expired galleries needed to be archived.');
            }

        } catch (\Exception $e) {
            $io->error('Failed to cleanup old expired galleries: ' . $e->getMessage());
        }
    }
}