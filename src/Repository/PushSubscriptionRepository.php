<?php

namespace App\Repository;

use App\Entity\PushSubscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PushSubscription>
 */
class PushSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PushSubscription::class);
    }

    /**
     * Trouve toutes les subscriptions actives d'un utilisateur
     *
     * @return PushSubscription[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('ps')
            ->where('ps.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ps.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une subscription par endpoint et utilisateur
     */
    public function findByUserAndEndpoint(User $user, string $endpoint): ?PushSubscription
    {
        return $this->createQueryBuilder('ps')
            ->where('ps.user = :user')
            ->andWhere('ps.endpoint = :endpoint')
            ->setParameter('user', $user)
            ->setParameter('endpoint', $endpoint)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les subscriptions pour un type de notification donné
     *
     * @return PushSubscription[]
     */
    public function findForNotificationType(string $type): array
    {
        $qb = $this->createQueryBuilder('ps');

        switch ($type) {
            case 'event_registration':
                $qb->where('ps.notifyEventRegistration = true');
                break;
            case 'event_cancellation':
                $qb->where('ps.notifyEventCancellation = true');
                break;
            case 'waiting_list_promotion':
                $qb->where('ps.notifyWaitingListPromotion = true');
                break;
            case 'as_dp':
                $qb->where('ps.notifyAsDP = true');
                break;
            default:
                return [];
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Supprime les subscriptions expirées (non utilisées depuis plus de 90 jours)
     */
    public function deleteExpiredSubscriptions(): int
    {
        $expirationDate = new \DateTimeImmutable('-90 days');

        return $this->createQueryBuilder('ps')
            ->delete()
            ->where('ps.lastUsedAt < :expirationDate OR (ps.lastUsedAt IS NULL AND ps.createdAt < :expirationDate)')
            ->setParameter('expirationDate', $expirationDate)
            ->getQuery()
            ->execute();
    }
}
