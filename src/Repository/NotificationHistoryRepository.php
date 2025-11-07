<?php

namespace App\Repository;

use App\Entity\NotificationHistory;
use App\Entity\User;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationHistory>
 */
class NotificationHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationHistory::class);
    }

    /**
     * Trouve l'historique des notifications d'un utilisateur
     *
     * @return NotificationHistory[]
     */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('nh')
            ->where('nh.user = :user')
            ->setParameter('user', $user)
            ->orderBy('nh.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les notifications pour un événement
     *
     * @return NotificationHistory[]
     */
    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('nh')
            ->where('nh.event = :event')
            ->setParameter('event', $event)
            ->orderBy('nh.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule les statistiques pour un utilisateur
     */
    public function getStatsByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('nh');

        return [
            'total' => $qb->select('COUNT(nh.id)')
                ->where('nh.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getSingleScalarResult(),

            'delivered' => $qb->select('COUNT(nh.id)')
                ->where('nh.user = :user')
                ->andWhere('nh.status IN (:statuses)')
                ->setParameter('user', $user)
                ->setParameter('statuses', ['delivered', 'opened', 'clicked'])
                ->getQuery()
                ->getSingleScalarResult(),

            'opened' => $qb->select('COUNT(nh.id)')
                ->where('nh.user = :user')
                ->andWhere('nh.status IN (:statuses)')
                ->setParameter('user', $user)
                ->setParameter('statuses', ['opened', 'clicked'])
                ->getQuery()
                ->getSingleScalarResult(),

            'clicked' => $qb->select('COUNT(nh.id)')
                ->where('nh.user = :user')
                ->andWhere('nh.status = :status')
                ->setParameter('user', $user)
                ->setParameter('status', 'clicked')
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }

    /**
     * Calcule les statistiques globales
     */
    public function getGlobalStats(\DateTimeInterface $since = null): array
    {
        $qb = $this->createQueryBuilder('nh');

        if ($since) {
            $qb->where('nh.createdAt >= :since')
               ->setParameter('since', $since);
        }

        $total = (int) $qb->select('COUNT(nh.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $delivered = (int) $qb->select('COUNT(nh.id)')
            ->where('nh.status IN (:statuses)')
            ->setParameter('statuses', ['delivered', 'opened', 'clicked'])
            ->getQuery()
            ->getSingleScalarResult();

        $opened = (int) $qb->select('COUNT(nh.id)')
            ->where('nh.status IN (:statuses)')
            ->setParameter('statuses', ['opened', 'clicked'])
            ->getQuery()
            ->getSingleScalarResult();

        $clicked = (int) $qb->select('COUNT(nh.id)')
            ->where('nh.status = :status')
            ->setParameter('status', 'clicked')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'delivered' => $delivered,
            'opened' => $opened,
            'clicked' => $clicked,
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            'open_rate' => $delivered > 0 ? round(($opened / $delivered) * 100, 2) : 0,
            'click_rate' => $opened > 0 ? round(($clicked / $opened) * 100, 2) : 0,
        ];
    }

    /**
     * Supprime l'historique ancien (plus de X jours)
     */
    public function deleteOldHistory(int $days = 90): int
    {
        $date = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('nh')
            ->delete()
            ->where('nh.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Trouve les notifications récentes pour groupement
     *
     * @return NotificationHistory[]
     */
    public function findRecentForGrouping(User $user, string $type, int $minutesAgo = 5): array
    {
        $since = new \DateTimeImmutable("-{$minutesAgo} minutes");

        return $this->createQueryBuilder('nh')
            ->where('nh.user = :user')
            ->andWhere('nh.type = :type')
            ->andWhere('nh.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('since', $since)
            ->orderBy('nh.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
