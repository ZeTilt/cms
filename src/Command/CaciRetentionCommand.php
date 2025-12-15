<?php

namespace App\Command;

use App\Repository\CaciAccessLogRepository;
use App\Repository\MedicalCertificateRepository;
use App\Service\CaciService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:caci:retention',
    description: 'Delete expired CACIs according to RGPD retention policy',
)]
class CaciRetentionCommand extends Command
{
    public function __construct(
        private MedicalCertificateRepository $certificateRepository,
        private CaciAccessLogRepository $accessLogRepository,
        private CaciService $caciService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate without actually deleting')
            ->addOption('logs-retention', null, InputOption::VALUE_REQUIRED, 'Delete access logs older than X years', '3')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $logsRetentionYears = (int) $input->getOption('logs-retention');

        if ($dryRun) {
            $io->note('Mode simulation activé - aucune suppression effective');
        }

        // 1. Delete certificates scheduled for deletion
        $io->section('Suppression des CACI planifiés');

        $scheduledCertificates = $this->certificateRepository->findScheduledForDeletion();
        $deletedCount = 0;

        foreach ($scheduledCertificates as $certificate) {
            $io->writeln(sprintf(
                '  - CACI de %s (expire: %s, suppression prévue: %s)',
                $certificate->getUser()->getFullName(),
                $certificate->getExpiryDate()->format('d/m/Y'),
                $certificate->getScheduledDeletionDate()->format('d/m/Y')
            ));

            if (!$dryRun) {
                $this->caciService->deleteCertificate($certificate);
            }
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            $io->success(sprintf('%d CACI supprimé(s)', $deletedCount));
        } else {
            $io->info('Aucun CACI à supprimer');
        }

        // 2. Delete old access logs
        $io->section('Suppression des logs d\'accès anciens');

        $logsRetentionDate = new \DateTime("-{$logsRetentionYears} years");
        $io->writeln(sprintf('  Suppression des logs antérieurs au %s', $logsRetentionDate->format('d/m/Y')));

        if (!$dryRun) {
            $deletedLogs = $this->accessLogRepository->deleteOlderThan($logsRetentionDate);
            if ($deletedLogs > 0) {
                $io->success(sprintf('%d logs d\'accès supprimés', $deletedLogs));
            } else {
                $io->info('Aucun log à supprimer');
            }
        } else {
            $io->info('Mode simulation - logs non supprimés');
        }

        $io->success('Politique de rétention RGPD appliquée avec succès');

        return Command::SUCCESS;
    }
}
