<?php

namespace App\Repository;

use App\Entity\CaciAccessLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CaciAccessLog>
 */
class CaciAccessLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CaciAccessLog::class);
    }

    /**
     * Get all access logs for a specific user's CACI
     */
    public function findByTargetUser(User $user): array
    {
        return $this->createQueryBuilder('log')
            ->where('log.targetUser = :user')
            ->setParameter('user', $user)
            ->orderBy('log.accessedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all access logs made by a specific user
     */
    public function findByAccessedBy(User $user): array
    {
        return $this->createQueryBuilder('log')
            ->where('log.accessedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('log.accessedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get access logs for RGPD export
     */
    public function findForRgpdExport(User $user): array
    {
        return $this->createQueryBuilder('log')
            ->where('log.targetUser = :user')
            ->setParameter('user', $user)
            ->orderBy('log.accessedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete old logs (for retention policy)
     */
    public function deleteOlderThan(\DateTimeInterface $date): int
    {
        return $this->createQueryBuilder('log')
            ->delete()
            ->where('log.accessedAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
