<?php

namespace App\Controller\DP;

use App\Repository\NotificationHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dp/notifications')]
#[IsGranted('ROLE_DP')]
class NotificationAnalyticsController extends AbstractController
{
    public function __construct(
        private NotificationHistoryRepository $historyRepository
    ) {}

    /**
     * Dashboard des analytics de notifications
     */
    #[Route('/analytics', name: 'dp_notification_analytics')]
    public function analytics(Request $request): Response
    {
        // Période sélectionnée (7 jours, 30 jours, 90 jours, tout)
        $period = $request->query->get('period', '30');
        $since = null;

        if ($period !== 'all') {
            $since = new \DateTimeImmutable("-{$period} days");
        }

        // Statistiques globales
        $globalStats = $this->historyRepository->getGlobalStats($since);

        // Statistiques de l'utilisateur connecté
        $user = $this->getUser();
        $userStats = $this->historyRepository->getStatsByUser($user);

        // Historique récent
        $recentNotifications = $this->historyRepository->createQueryBuilder('nh')
            ->orderBy('nh.createdAt', 'DESC')
            ->setMaxResults(20);

        if ($since) {
            $recentNotifications
                ->where('nh.createdAt >= :since')
                ->setParameter('since', $since);
        }

        $notifications = $recentNotifications->getQuery()->getResult();

        // Statistiques par type
        $statsByType = $this->getStatsByType($since);

        return $this->render('dp/notification_analytics/index.html.twig', [
            'globalStats' => $globalStats,
            'userStats' => $userStats,
            'notifications' => $notifications,
            'statsByType' => $statsByType,
            'period' => $period
        ]);
    }

    /**
     * Récupère les statistiques par type de notification
     */
    private function getStatsByType(?\DateTimeInterface $since): array
    {
        $qb = $this->historyRepository->createQueryBuilder('nh')
            ->select('nh.type, COUNT(nh.id) as total, ' .
                'SUM(CASE WHEN nh.status IN (:delivered_statuses) THEN 1 ELSE 0 END) as delivered, ' .
                'SUM(CASE WHEN nh.status IN (:opened_statuses) THEN 1 ELSE 0 END) as opened, ' .
                'SUM(CASE WHEN nh.status = :clicked_status THEN 1 ELSE 0 END) as clicked')
            ->groupBy('nh.type')
            ->setParameter('delivered_statuses', ['delivered', 'opened', 'clicked'])
            ->setParameter('opened_statuses', ['opened', 'clicked'])
            ->setParameter('clicked_status', 'clicked');

        if ($since) {
            $qb->where('nh.createdAt >= :since')
               ->setParameter('since', $since);
        }

        $results = $qb->getQuery()->getResult();

        // Calculer les taux pour chaque type
        return array_map(function($row) {
            $total = (int) $row['total'];
            $delivered = (int) $row['delivered'];
            $opened = (int) $row['opened'];
            $clicked = (int) $row['clicked'];

            return [
                'type' => $row['type'],
                'total' => $total,
                'delivered' => $delivered,
                'opened' => $opened,
                'clicked' => $clicked,
                'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
                'open_rate' => $delivered > 0 ? round(($opened / $delivered) * 100, 2) : 0,
                'click_rate' => $opened > 0 ? round(($clicked / $opened) * 100, 2) : 0,
            ];
        }, $results);
    }
}
