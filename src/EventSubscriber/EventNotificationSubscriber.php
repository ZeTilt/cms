<?php

namespace App\EventSubscriber;

use App\Entity\Event;
use App\Repository\UserRepository;
use App\Service\PushNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Event::class)]
class EventNotificationSubscriber
{
    public function __construct(
        private PushNotificationService $pushService,
        private UserRepository $userRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Appelé après la création d'un nouvel événement
     */
    public function postPersist(Event $event, PostPersistEventArgs $args): void
    {
        // Ne notifier que si l'événement est actif
        if ($event->getStatus() !== 'active') {
            return;
        }

        // Récupérer tous les utilisateurs actifs
        $allUsers = $this->userRepository->findBy(['status' => 'active']);

        // Filtrer les utilisateurs éligibles
        $eligibleUsers = [];
        foreach ($allUsers as $user) {
            if ($this->isUserEligible($user, $event)) {
                $eligibleUsers[] = $user;
            }
        }

        if (empty($eligibleUsers)) {
            $this->logger->info('No eligible users for new event notification', [
                'event_id' => $event->getId()
            ]);
            return;
        }

        // Préparer la notification
        $notification = [
            'type' => 'new_event',
            'event' => $event,
            'title' => 'Nouvelle plongée disponible !',
            'body' => sprintf(
                '%s - %s',
                $event->getTitle(),
                $event->getStartDate()->format('d/m/Y à H:i')
            ),
            'url' => '/calendrier/' . $event->getId(),
            'tag' => 'new-event-' . $event->getId(),
            'groupTag' => 'new-events',
            'groupable' => true,
            'requireInteraction' => false,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Voir l\'événement',
                    'icon' => '/pwa-icons/icon-72x72.png'
                ]
            ]
        ];

        // Ajouter info sur le niveau si requis
        if ($event->getMinDivingLevel()) {
            $notification['body'] .= sprintf(' (niveau min: %s)', $event->getMinDivingLevel()->getName());
        }

        // Ajouter info sur les places disponibles
        $availableSpots = $event->getAvailableSpots();
        if ($availableSpots !== null) {
            $notification['body'] .= sprintf(' - %d place(s) disponible(s)', $availableSpots);
        }

        // Envoyer la notification à tous les utilisateurs éligibles
        $sent = $this->pushService->sendToUsers($eligibleUsers, $notification, 'new_event');

        $this->logger->info('New event notifications sent', [
            'event_id' => $event->getId(),
            'eligible_users' => count($eligibleUsers),
            'notifications_sent' => $sent
        ]);
    }

    /**
     * Vérifie si un utilisateur est éligible pour un événement
     */
    private function isUserEligible($user, Event $event): bool
    {
        // Vérifier le niveau minimum requis
        $minLevel = $event->getMinDivingLevel();

        if ($minLevel === null) {
            // Pas de niveau minimum requis, tout le monde est éligible
            return true;
        }

        $userLevel = $user->getHighestDivingLevel();

        if ($userLevel === null) {
            // L'utilisateur n'a pas de niveau, il n'est pas éligible
            return false;
        }

        // Comparer les niveaux (sortOrder plus élevé = niveau plus avancé)
        return $userLevel->getSortOrder() >= $minLevel->getSortOrder();
    }
}
