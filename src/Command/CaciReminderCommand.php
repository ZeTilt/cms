<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:caci:send-reminders',
    description: 'Envoie des rappels email pour les CACI expirant bientôt (J-30 et J-7)',
)]
class CaciReminderCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule l\'envoi sans envoyer réellement les emails')
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Nombre de jours avant expiration', '30,7')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $daysOption = $input->getOption('days');
        $daysArray = array_map('intval', explode(',', $daysOption));

        if ($dryRun) {
            $io->note('Mode simulation activé - aucun email ne sera envoyé');
        }

        $totalSent = 0;

        foreach ($daysArray as $days) {
            $io->section("Rappels J-{$days}");

            // Trouver les utilisateurs dont le CACI expire exactement dans X jours
            $users = $this->findUsersExpiringIn($days);

            if (empty($users)) {
                $io->info("Aucun CACI n'expire dans {$days} jours.");
                continue;
            }

            $io->info(sprintf('%d CACI expire(nt) dans %d jours.', count($users), $days));

            foreach ($users as $user) {
                $io->text(sprintf(
                    '  - %s <%s> (expire le %s)',
                    $user->getFullName(),
                    $user->getEmail(),
                    $user->getMedicalCertificateExpiry()->format('d/m/Y')
                ));

                if (!$dryRun) {
                    try {
                        $this->sendReminderEmail($user, $days);
                        $totalSent++;
                    } catch (\Exception $e) {
                        $io->error(sprintf('Erreur envoi email à %s: %s', $user->getEmail(), $e->getMessage()));
                    }
                }
            }
        }

        if ($dryRun) {
            $io->success(sprintf('Simulation terminée. %d email(s) auraient été envoyés.', $totalSent));
        } else {
            $io->success(sprintf('%d rappel(s) envoyé(s) avec succès.', $totalSent));
        }

        return Command::SUCCESS;
    }

    private function findUsersExpiringIn(int $days): array
    {
        // On cherche les CACI expirant exactement dans X jours (entre 00:00 et 23:59)
        $startOfDay = (new \DateTime('today'))->modify("+{$days} days")->setTime(0, 0, 0);
        $endOfDay = (new \DateTime('today'))->modify("+{$days} days")->setTime(23, 59, 59);

        return $this->userRepository->createQueryBuilder('u')
            ->andWhere('u.active = :active')
            ->andWhere('u.medicalCertificateExpiry IS NOT NULL')
            ->andWhere('u.medicalCertificateExpiry >= :startOfDay')
            ->andWhere('u.medicalCertificateExpiry <= :endOfDay')
            ->setParameter('active', true)
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->getQuery()
            ->getResult();
    }

    private function sendReminderEmail($user, int $daysRemaining): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@plongee-venetes.fr', 'Club Vénètes'))
            ->to($user->getEmail())
            ->subject($this->getSubject($daysRemaining))
            ->htmlTemplate('emails/caci_reminder.html.twig')
            ->context([
                'user' => $user,
                'daysRemaining' => $daysRemaining,
                'expiryDate' => $user->getMedicalCertificateExpiry(),
            ]);

        $this->mailer->send($email);
    }

    private function getSubject(int $daysRemaining): string
    {
        if ($daysRemaining <= 7) {
            return sprintf('⚠️ Votre CACI expire dans %d jour(s) !', $daysRemaining);
        }
        return sprintf('Rappel : Votre CACI expire dans %d jours', $daysRemaining);
    }
}
