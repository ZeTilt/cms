<?php

namespace App\Controller\Api;

use App\Entity\PushSubscription;
use App\Repository\NotificationHistoryRepository;
use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/push')]
#[IsGranted('ROLE_USER')]
class PushSubscriptionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PushSubscriptionRepository $subscriptionRepository,
        private NotificationHistoryRepository $historyRepository
    ) {}

    /**
     * S'abonner aux notifications push
     */
    #[Route('/subscribe', name: 'api_push_subscribe', methods: ['POST'])]
    public function subscribe(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['endpoint'], $data['keys'])) {
            return new JsonResponse(['error' => 'Invalid subscription data'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Vérifier si une subscription existe déjà
            $existing = $this->subscriptionRepository->findByUserAndEndpoint(
                $user,
                $data['endpoint']
            );

            if ($existing) {
                // Mettre à jour la subscription existante
                $subscription = $existing;
            } else {
                // Créer une nouvelle subscription
                $subscription = new PushSubscription();
                $subscription->setUser($user);
                $subscription->setEndpoint($data['endpoint']);
            }

            $subscription->setPublicKey($data['keys']['p256dh'] ?? '');
            $subscription->setAuthToken($data['keys']['auth'] ?? '');
            $subscription->setUserAgent($request->headers->get('User-Agent'));

            // Sauvegarder les préférences si fournies
            if (isset($data['preferences'])) {
                $this->updatePreferences($subscription, $data['preferences']);
            }

            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Abonnement aux notifications enregistré'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to save subscription: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Se désabonner des notifications push
     */
    #[Route('/unsubscribe', name: 'api_push_unsubscribe', methods: ['POST'])]
    public function unsubscribe(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['endpoint'])) {
            return new JsonResponse(['error' => 'Endpoint required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $subscription = $this->subscriptionRepository->findByUserAndEndpoint(
                $user,
                $data['endpoint']
            );

            if ($subscription) {
                $this->entityManager->remove($subscription);
                $this->entityManager->flush();
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Désabonnement effectué'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to unsubscribe: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mettre à jour les préférences de notifications
     */
    #[Route('/preferences', name: 'api_push_preferences', methods: ['POST'])]
    public function updatePreferencesRoute(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['endpoint'], $data['preferences'])) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $subscription = $this->subscriptionRepository->findByUserAndEndpoint(
                $user,
                $data['endpoint']
            );

            if (!$subscription) {
                return new JsonResponse(['error' => 'Subscription not found'], Response::HTTP_NOT_FOUND);
            }

            $this->updatePreferences($subscription, $data['preferences']);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Préférences mises à jour'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to update preferences: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer les subscriptions de l'utilisateur
     */
    #[Route('/subscriptions', name: 'api_push_subscriptions', methods: ['GET'])]
    public function getSubscriptions(): JsonResponse
    {
        $user = $this->getUser();
        $subscriptions = $this->subscriptionRepository->findByUser($user);

        $data = array_map(function (PushSubscription $sub) {
            return [
                'id' => $sub->getId(),
                'endpoint' => substr($sub->getEndpoint(), 0, 50) . '...',
                'userAgent' => $sub->getUserAgent(),
                'createdAt' => $sub->getCreatedAt()->format('Y-m-d H:i:s'),
                'preferences' => [
                    'notifyEventRegistration' => $sub->isNotifyEventRegistration(),
                    'notifyEventCancellation' => $sub->isNotifyEventCancellation(),
                    'notifyWaitingListPromotion' => $sub->isNotifyWaitingListPromotion(),
                    'notifyEventReminder' => $sub->isNotifyEventReminder(),
                    'notifyNewEvent' => $sub->isNotifyNewEvent(),
                    'notifyAsDP' => $sub->isNotifyAsDP(),
                ]
            ];
        }, $subscriptions);

        return new JsonResponse($data);
    }

    /**
     * Marquer une notification comme ouverte
     */
    #[Route('/notification/{id}/opened', name: 'api_push_notification_opened', methods: ['POST'])]
    public function notificationOpened(int $id): JsonResponse
    {
        $user = $this->getUser();
        $notification = $this->historyRepository->find($id);

        if (!$notification || $notification->getUser() !== $user) {
            return new JsonResponse(['error' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$notification->getOpenedAt()) {
            $notification->markAsOpened();
            $this->entityManager->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Marquer une notification comme cliquée
     */
    #[Route('/notification/{id}/clicked', name: 'api_push_notification_clicked', methods: ['POST'])]
    public function notificationClicked(int $id): JsonResponse
    {
        $user = $this->getUser();
        $notification = $this->historyRepository->find($id);

        if (!$notification || $notification->getUser() !== $user) {
            return new JsonResponse(['error' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$notification->getClickedAt()) {
            $notification->markAsClicked();
            $this->entityManager->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Récupérer les statistiques des notifications de l'utilisateur
     */
    #[Route('/stats', name: 'api_push_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $user = $this->getUser();
        $stats = $this->historyRepository->getStatsByUser($user);

        return new JsonResponse($stats);
    }

    /**
     * Helper pour mettre à jour les préférences
     */
    private function updatePreferences(PushSubscription $subscription, array $preferences): void
    {
        if (isset($preferences['notifyEventRegistration'])) {
            $subscription->setNotifyEventRegistration((bool) $preferences['notifyEventRegistration']);
        }
        if (isset($preferences['notifyEventCancellation'])) {
            $subscription->setNotifyEventCancellation((bool) $preferences['notifyEventCancellation']);
        }
        if (isset($preferences['notifyWaitingListPromotion'])) {
            $subscription->setNotifyWaitingListPromotion((bool) $preferences['notifyWaitingListPromotion']);
        }
        if (isset($preferences['notifyEventReminder'])) {
            $subscription->setNotifyEventReminder((bool) $preferences['notifyEventReminder']);
        }
        if (isset($preferences['notifyNewEvent'])) {
            $subscription->setNotifyNewEvent((bool) $preferences['notifyNewEvent']);
        }
        if (isset($preferences['notifyAsDP'])) {
            $subscription->setNotifyAsDP((bool) $preferences['notifyAsDP']);
        }
    }
}
