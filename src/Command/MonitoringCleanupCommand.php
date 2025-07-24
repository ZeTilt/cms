<?php

namespace App\Command;

use App\Service\MonitoringService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:monitoring:cleanup',
    description: 'Clean up old monitoring metrics to prevent database bloat'
)]
class MonitoringCleanupCommand extends Command
{
    public function __construct(
        private MonitoringService $monitoringService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'older-than-hours',
                null,
                InputOption::VALUE_OPTIONAL,
                'Remove metrics older than specified hours',
                24
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be deleted without actually deleting'
            )
            ->setHelp('
This command cleans up old monitoring metrics to prevent database bloat.

Examples:
  # Clean metrics older than 24 hours (default)
  php bin/console app:monitoring:cleanup
  
  # Clean metrics older than 48 hours
  php bin/console app:monitoring:cleanup --older-than-hours=48
  
  # See what would be deleted without actually deleting
  php bin/console app:monitoring:cleanup --dry-run
  
  # Recommended cron job (daily cleanup at 2 AM)
  # 0 2 * * * /path/to/php /path/to/bin/console app:monitoring:cleanup --older-than-hours=72
            ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $olderThanHours = (int) $input->getOption('older-than-hours');
        $isDryRun = $input->getOption('dry-run');

        // Validation
        if ($olderThanHours < 1) {
            $io->error('The --older-than-hours option must be at least 1.');
            return Command::FAILURE;
        }

        if ($olderThanHours < 24) {
            $io->warning('Cleaning metrics newer than 24 hours is not recommended as it may affect dashboard functionality.');
            if (!$io->confirm('Do you want to continue?', false)) {
                $io->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $io->title('ZeTilt CMS - Monitoring Metrics Cleanup');
        
        if ($isDryRun) {
            $io->note('DRY RUN MODE - No data will be actually deleted');
        }

        $io->info(sprintf(
            'Cleaning up monitoring metrics older than %d hours (%s)',
            $olderThanHours,
            $this->formatTimeAgo($olderThanHours)
        ));

        try {
            if ($isDryRun) {
                $this->performDryRun($io, $olderThanHours);
            } else {
                $this->monitoringService->cleanupOldMetrics($olderThanHours);
                $io->success(sprintf(
                    'Successfully cleaned up monitoring metrics older than %d hours.',
                    $olderThanHours
                ));
            }

            $this->showRecommendations($io);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to clean up monitoring metrics: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function performDryRun(SymfonyStyle $io, int $olderThanHours): void
    {
        $cutoffTime = time() - ($olderThanHours * 3600);
        $cutoffDate = date('Y-m-d H:i:s', $cutoffTime);
        
        $io->section('Dry Run Results');
        $io->text([
            'The following metrics would be deleted:',
            sprintf('  • All performance metrics older than %s', $cutoffDate),
            sprintf('  • All cache operation metrics older than %s', $cutoffDate),
            sprintf('  • All content operation metrics older than %s', $cutoffDate),
            sprintf('  • All security event metrics older than %s', $cutoffDate),
        ]);
        
        $io->note('To actually perform the cleanup, run this command without the --dry-run option.');
    }

    private function formatTimeAgo(int $hours): string
    {
        if ($hours < 24) {
            return sprintf('%d hour%s ago', $hours, $hours > 1 ? 's' : '');
        }
        
        $days = round($hours / 24, 1);
        if ($days == (int) $days) {
            $days = (int) $days;
        }
        
        return sprintf('%.1f day%s ago', $days, $days > 1 ? 's' : '');
    }

    private function showRecommendations(SymfonyStyle $io): void
    {
        $io->section('Recommendations');
        
        $io->text([
            '📅 <comment>Automation:</comment> Set up a cron job to run this command automatically:',
            '   <info>0 2 * * * /path/to/php /path/to/bin/console app:monitoring:cleanup --older-than-hours=72</info>',
            '',
            '⚙️  <comment>Frequency:</comment> Daily cleanup is recommended to maintain optimal performance.',
            '',
            '📊 <comment>Retention:</comment> Keep 72 hours of data for debugging, longer for analytics if needed.',
            '',
            '🔍 <comment>Monitoring:</comment> Check /admin dashboard to verify metrics are still available.',
        ]);
    }
}