<?php

namespace App\EventSubscriber;

use App\Entity\EventParticipation;
use App\Service\PushNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: EventParticipation::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: EventParticipation::class)]
class EventParticipationNotificationSubscriber
{
    public function __construct(
        private PushNotificationService $pushService,
        private LoggerInterface $logger
    ) {}

    /**
     * Appelé après la création d'une nouvelle participation
     */
    public function postPersist(EventParticipation $participation, PostPersistEventArgs $args): void
    {
        // Nouvelle inscription : notifier le DP
        if ($participation->isActive()) {
            $this->notifyDPOfNewRegistration($participation);
        }
    }

    /**
     * Appelé après la mise à jour d'une participation
     */
    public function postUpdate(EventParticipation $participation, PostUpdateEventArgs $args): void
    {
        $changeSet = $args->getEntityChangeSet();

        // Désinscription (status change vers cancelled)
        if (isset($changeSet['status']) && $participation->getStatus() === 'cancelled') {
            $this->handleCancellation($participation);
        }

        // Promotion de la liste d'attente (isWaitingList: true → false)
        if (isset($changeSet['isWaitingList']) &&
            $changeSet['isWaitingList'][0] === true &&
            $changeSet['isWaitingList'][1] === false) {
            $this->handleWaitingListPromotion($participation);
        }
    }

    /**
     * Notifie le DP qu'un nouveau participant s'est inscrit
     */
    private function notifyDPOfNewRegistration(EventParticipation $participation): void
    {
        $event = $participation->getEvent();
        $participant = $participation->getParticipant();

        $notification = [
            'type' => 'event_registration',
            'event' => $event,
            'title' => 'Nouvelle inscription',
            'titleTemplate' => 'Nouvelle inscription',
            'body' => sprintf(
                '%s s\'est inscrit à %s le %s',
                $participant->getFullName(),
                $event->getTitle(),
                $event->getStartDate()->format('d/m/Y à H:i')
            ),
            'url' => '/dp/events/' . $event->getId() . '/participants',
            'tag' => 'event-registration-' . $event->getId(),
            'groupTag' => 'event-' . $event->getId() . '-registrations',
            'groupable' => true,
            'requireInteraction' => false,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Voir les participants',
                    'icon' => '/pwa-icons/icon-72x72.png'
                ]
            ]
        ];

        if ($participation->isWaitingList()) {
            $notification['title'] = 'Nouvelle inscription (liste d\'attente)';
            $notification['titleTemplate'] = 'Nouvelle inscription (liste d\'attente)';
            $notification['body'] .= ' (liste d\'attente)';
        }

        $this->pushService->sendToEventDP($event, $notification);

        $this->logger->info('DP notified of new registration', [
            'event_id' => $event->getId(),
            'participant_id' => $participant->getId()
        ]);
    }

    /**
     * Gère une désinscription et notifie le DP
     */
    private function handleCancellation(EventParticipation $participation): void
    {
        $event = $participation->getEvent();
        $participant = $participation->getParticipant();

        // Vérifier s'il reste des places
        $availableSpots = $event->getAvailableSpots();

        $notification = [
            'type' => 'event_cancellation',
            'event' => $event,
            'title' => 'Désinscription',
            'body' => sprintf(
                '%s s\'est désinscrit de %s',
                $participant->getFullName(),
                $event->getTitle()
            ),
            'url' => '/dp/events/' . $event->getId() . '/participants',
            'tag' => 'event-cancellation-' . $event->getId(),
            'groupTag' => 'event-' . $event->getId() . '-cancellations',
            'groupable' => false,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Voir les participants',
                    'icon' => '/pwa-icons/icon-72x72.png'
                ]
            ]
        ];

        if ($availableSpots > 0) {
            $notification['body'] .= sprintf(' (%d place(s) restante(s))', $availableSpots);
        }

        $this->pushService->sendToEventDP($event, $notification);

        $this->logger->info('DP notified of cancellation', [
            'event_id' => $event->getId(),
            'participant_id' => $participant->getId()
        ]);
    }

    /**
     * Gère la promotion d'un participant de la liste d'attente
     */
    private function handleWaitingListPromotion(EventParticipation $participation): void
    {
        $event = $participation->getEvent();
        $participant = $participation->getParticipant();

        // Notifier le participant promu
        $participantNotification = [
            'type' => 'waiting_list_promotion',
            'event' => $event,
            'title' => 'Une place s\'est libérée !',
            'body' => sprintf(
                'Votre inscription à %s le %s est confirmée.',
                $event->getTitle(),
                $event->getStartDate()->format('d/m/Y à H:i')
            ),
            'url' => '/calendrier/evenement/' . $event->getId(),
            'tag' => 'waiting-list-promoted-' . $participation->getId(),
            'groupTag' => null,
            'groupable' => false,
            'requireInteraction' => true,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Voir l\'événement',
                    'icon' => '/pwa-icons/icon-72x72.png'
                ],
                [
                    'action' => 'close',
                    'title' => 'Plus tard',
                    'icon' => '/pwa-icons/icon-72x72.png'
                ]
            ]
        ];

        $this->pushService->sendToUser(
            $participant,
            $participantNotification,
            'waiting_list_promotion'
        );

        // Notifier aussi le DP
        $dpNotification = [
            'type' => 'waiting_list_promotion',
            'event' => $event,
            'title' => 'Promotion liste d\'attente',
            'body' => sprintf(
                '%s a été promu de la liste d\'attente pour %s',
                $participant->getFullName(),
                $event->getTitle()
            ),
            'url' => '/dp/events/' . $event->getId() . '/participants',
            'tag' => 'dp-waiting-list-promoted-' . $participation->getId(),
            'groupTag' => null,
            'groupable' => false,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Voir les participants',
                    'icon' => '/pwa-icons/icon-72x72.png'
                ]
            ]
        ];

        $this->pushService->sendToEventDP($event, $dpNotification);

        $this->logger->info('Participant and DP notified of waiting list promotion', [
            'event_id' => $event->getId(),
            'participant_id' => $participant->getId()
        ]);
    }
}
