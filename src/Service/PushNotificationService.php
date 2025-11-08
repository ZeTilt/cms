<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\NotificationHistory;
use App\Entity\PushSubscription;
use App\Entity\User;
use App\Repository\NotificationHistoryRepository;
use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Log\LoggerInterface;

class PushNotificationService
{
    public function __construct(
        private PushSubscriptionRepository $subscriptionRepository,
        private NotificationHistoryRepository $historyRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private string $vapidPublicKey,
        private string $vapidPrivateKey,
        private string $vapidSubject
    ) {}

    /**
     * Envoie une notification push à un utilisateur spécifique
     */
    public function sendToUser(User $user, array $notification, ?string $notificationType = null): int
    {
        $subscriptions = $this->subscriptionRepository->findByUser($user);

        // Filtrer par type de notification si spécifié
        if ($notificationType) {
            $subscriptions = array_filter($subscriptions, function (PushSubscription $sub) use ($notificationType) {
                return $this->shouldSendNotification($sub, $notificationType);
            });
        }

        return $this->sendToSubscriptions($subscriptions, $notification);
    }

    /**
     * Envoie une notification au DP d'un événement
     */
    public function sendToEventDP(Event $event, array $notification): int
    {
        if (!$dp = $event->getDivingDirector()) {
            return 0;
        }

        return $this->sendToUser($dp, $notification, 'as_dp');
    }

    /**
     * Envoie une notification à plusieurs utilisateurs
     *
     * @param User[] $users
     */
    public function sendToUsers(array $users, array $notification, ?string $notificationType = null): int
    {
        $totalSent = 0;

        foreach ($users as $user) {
            $totalSent += $this->sendToUser($user, $notification, $notificationType);
        }

        return $totalSent;
    }

    /**
     * Envoie les notifications à une liste de subscriptions
     *
     * @param PushSubscription[] $subscriptions
     */
    private function sendToSubscriptions(array $subscriptions, array $notification): int
    {
        $sent = 0;
        $failed = [];
        $histories = [];

        // Vérifier si on doit grouper les notifications
        $shouldGroup = $notification['groupable'] ?? false;
        $groupTag = $notification['groupTag'] ?? null;

        foreach ($subscriptions as $subscription) {
            $user = $subscription->getUser();

            // Vérifier s'il y a des notifications récentes similaires pour groupement
            if ($shouldGroup && $groupTag) {
                $recentNotifications = $this->historyRepository->findRecentForGrouping(
                    $user,
                    $notification['type'] ?? 'general',
                    5 // 5 minutes
                );

                if (count($recentNotifications) > 0) {
                    // Grouper: mettre à jour le titre/body pour refléter le nombre
                    $count = count($recentNotifications) + 1;
                    $notification['title'] = sprintf('(%d) %s', $count, $notification['titleTemplate'] ?? $notification['title']);
                    $notification['body'] = sprintf('%d nouvelles notifications', $count);
                }
            }

            // Créer l'historique
            $history = new NotificationHistory();
            $history->setUser($user);
            $history->setType($notification['type'] ?? 'general');
            $history->setTitle($notification['title']);
            $history->setBody($notification['body']);
            $history->setUrl($notification['url'] ?? null);
            $history->setGroupTag($groupTag);

            if (isset($notification['event']) && $notification['event'] instanceof Event) {
                $history->setEvent($notification['event']);
            }

            try {
                $success = $this->sendPushNotification($subscription, $notification);

                if ($success) {
                    $subscription->updateLastUsedAt();
                    $history->markAsDelivered();
                    $sent++;
                } else {
                    $history->markAsFailed();
                    $failed[] = $subscription->getId();
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to send push notification', [
                    'subscription_id' => $subscription->getId(),
                    'error' => $e->getMessage()
                ]);
                $history->markAsFailed();
                $failed[] = $subscription->getId();
            }

            $histories[] = $history;
        }

        // Sauvegarder les historiques et lastUsedAt
        foreach ($histories as $history) {
            $this->entityManager->persist($history);
        }

        if ($sent > 0 || count($histories) > 0) {
            $this->entityManager->flush();
        }

        // Supprimer les subscriptions qui ont échoué (probablement expirées/invalides)
        if (!empty($failed)) {
            foreach ($failed as $id) {
                $sub = $this->subscriptionRepository->find($id);
                if ($sub) {
                    $this->entityManager->remove($sub);
                }
            }
            $this->entityManager->flush();
        }

        return $sent;
    }

    /**
     * Envoie réellement la notification push via l'API Web Push
     */
    private function sendPushNotification(PushSubscription $subscription, array $notification): bool
    {
        try {
            // Préparer le payload
            $payload = json_encode([
                'title' => $notification['title'] ?? 'Notification',
                'body' => $notification['body'] ?? '',
                'icon' => $notification['icon'] ?? '/pwa-icons/icon-192x192.png',
                'badge' => $notification['badge'] ?? '/pwa-icons/icon-72x72.png',
                'url' => $notification['url'] ?? '/',
                'tag' => $notification['tag'] ?? 'csv-notification',
                'requireInteraction' => $notification['requireInteraction'] ?? false,
                'actions' => $notification['actions'] ?? []
            ]);

            // Configuration de l'authentification VAPID
            $auth = [
                'VAPID' => [
                    'subject' => $this->vapidSubject,
                    'publicKey' => $this->vapidPublicKey,
                    'privateKey' => $this->vapidPrivateKey,
                ],
            ];

            // Créer l'instance WebPush
            $webPush = new WebPush($auth);

            // Créer la subscription à partir des données stockées
            $pushSubscription = Subscription::create([
                'endpoint' => $subscription->getEndpoint(),
                'keys' => [
                    'p256dh' => $subscription->getPublicKey(),
                    'auth' => $subscription->getAuthToken(),
                ],
            ]);

            // Envoyer la notification
            $result = $webPush->sendOneNotification($pushSubscription, $payload);

            // Vérifier le résultat
            if ($result->isSuccess()) {
                $this->logger->info('Push notification sent successfully', [
                    'user_id' => $subscription->getUser()->getId(),
                    'endpoint' => substr($subscription->getEndpoint(), 0, 50) . '...',
                ]);
                return true;
            } else {
                $this->logger->error('Push notification failed', [
                    'user_id' => $subscription->getUser()->getId(),
                    'endpoint' => substr($subscription->getEndpoint(), 0, 50) . '...',
                    'reason' => $result->getReason(),
                    'expired' => $result->isSubscriptionExpired(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception sending push notification', [
                'user_id' => $subscription->getUser()->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Vérifie si une notification doit être envoyée selon les préférences
     */
    private function shouldSendNotification(PushSubscription $subscription, string $type): bool
    {
        return match($type) {
            'event_registration' => $subscription->isNotifyEventRegistration(),
            'event_cancellation' => $subscription->isNotifyEventCancellation(),
            'waiting_list_promotion' => $subscription->isNotifyWaitingListPromotion(),
            'event_reminder' => $subscription->isNotifyEventReminder(),
            'new_event' => $subscription->isNotifyNewEvent(),
            'as_dp' => $subscription->isNotifyAsDP(),
            default => true
        };
    }
}
