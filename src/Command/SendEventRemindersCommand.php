<?php

namespace App\Command;

use App\Repository\EventRepository;
use App\Service\PushNotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Envoie des rappels pour les événements à venir'
)]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private EventRepository $eventRepository,
        private PushNotificationService $pushService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('hours', null, InputOption::VALUE_OPTIONAL, 'Nombre d\'heures avant l\'événement', 24)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simuler sans envoyer de notifications');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hours = (int) $input->getOption('hours');
        $dryRun = $input->getOption('dry-run');

        $io->title('Envoi des rappels d\'événements');

        if ($dryRun) {
            $io->warning('Mode DRY RUN - Aucune notification ne sera envoyée');
        }

        // Trouver les événements dans la fenêtre de temps
        $now = new \DateTimeImmutable();
        $targetStart = $now->modify("+{$hours} hours")->modify('-30 minutes');
        $targetEnd = $now->modify("+{$hours} hours")->modify('+30 minutes');

        $events = $this->eventRepository->findUpcomingEventsInRange($targetStart, $targetEnd);

        if (empty($events)) {
            $io->success(sprintf('Aucun événement trouvé entre %s et %s',
                $targetStart->format('d/m/Y H:i'),
                $targetEnd->format('d/m/Y H:i')
            ));
            return Command::SUCCESS;
        }

        $io->info(sprintf('Événements trouvés: %d', count($events)));

        $totalSent = 0;
        $totalParticipants = 0;

        foreach ($events as $event) {
            $io->section(sprintf('Événement: %s - %s',
                $event->getTitle(),
                $event->getStartDate()->format('d/m/Y à H:i')
            ));

            // Récupérer les participants confirmés (hors liste d'attente)
            $confirmedParticipations = $event->getConfirmedParticipations();
            $participantCount = count($confirmedParticipations);

            if ($participantCount === 0) {
                $io->warning('Aucun participant confirmé pour cet événement');
                continue;
            }

            $io->text(sprintf('Participants confirmés: %d', $participantCount));
            $totalParticipants += $participantCount;

            if (!$dryRun) {
                // Envoyer une notification à chaque participant
                foreach ($confirmedParticipations as $participation) {
                    $participant = $participation->getParticipant();

                    $notification = [
                        'type' => 'event_reminder',
                        'event' => $event,
                        'title' => 'Rappel : Événement demain',
                        'body' => sprintf(
                            '%s - %s (%d place(s))',
                            $event->getTitle(),
                            $event->getStartDate()->format('d/m/Y à H:i'),
                            $participation->getPlacesReserved()
                        ),
                        'url' => '/calendrier/' . $event->getId(),
                        'tag' => 'event-reminder-' . $event->getId(),
                        'groupTag' => null,
                        'groupable' => false,
                        'requireInteraction' => true,
                        'actions' => [
                            [
                                'action' => 'view',
                                'title' => 'Voir l\'événement',
                                'icon' => '/pwa-icons/icon-72x72.png'
                            ]
                        ]
                    ];

                    $sent = $this->pushService->sendToUser($participant, $notification, 'event_reminder');
                    $totalSent += $sent;
                }

                // Envoyer un récapitulatif au DP
                if ($dp = $event->getDivingDirector()) {
                    $dpNotification = [
                        'type' => 'event_reminder_dp',
                        'event' => $event,
                        'title' => 'Rappel DP : Événement demain',
                        'body' => sprintf(
                            '%s - %s (%d participants confirmés)',
                            $event->getTitle(),
                            $event->getStartDate()->format('d/m/Y à H:i'),
                            $participantCount
                        ),
                        'url' => '/dp/events/' . $event->getId() . '/participants',
                        'tag' => 'dp-event-reminder-' . $event->getId(),
                        'groupTag' => null,
                        'groupable' => false,
                        'requireInteraction' => false,
                        'actions' => [
                            [
                                'action' => 'view',
                                'title' => 'Voir les participants',
                                'icon' => '/pwa-icons/icon-72x72.png'
                            ]
                        ]
                    ];

                    $sent = $this->pushService->sendToEventDP($event, $dpNotification);
                    $totalSent += $sent;
                    $io->text(sprintf('✓ Notification DP envoyée (%d)', $sent));
                }

                $io->success(sprintf('Rappels envoyés pour "%s"', $event->getTitle()));
            } else {
                $io->text('[DRY RUN] Notifications seraient envoyées');
            }
        }

        $io->success(sprintf(
            'Terminé : %d événements traités, %d participants, %d notifications envoyées',
            count($events),
            $totalParticipants,
            $totalSent
        ));

        $this->logger->info('Event reminders sent', [
            'events_count' => count($events),
            'participants_count' => $totalParticipants,
            'notifications_sent' => $totalSent
        ]);

        return Command::SUCCESS;
    }
}
