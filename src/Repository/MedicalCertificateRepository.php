<?php

namespace App\Repository;

use App\Entity\MedicalCertificate;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MedicalCertificate>
 */
class MedicalCertificateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MedicalCertificate::class);
    }

    /**
     * Get the current (latest) certificate for a user
     */
    public function findCurrentForUser(User $user): ?MedicalCertificate
    {
        return $this->createQueryBuilder('mc')
            ->where('mc.user = :user')
            ->andWhere('mc.status != :rejected')
            ->setParameter('user', $user)
            ->setParameter('rejected', MedicalCertificate::STATUS_REJECTED)
            ->orderBy('mc.expiryDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all certificates pending validation
     */
    public function findPendingValidation(): array
    {
        return $this->createQueryBuilder('mc')
            ->where('mc.status = :pending')
            ->setParameter('pending', MedicalCertificate::STATUS_PENDING)
            ->orderBy('mc.uploadedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get certificates for users registered to events led by a DP
     */
    public function findForDpEvents(User $dp): array
    {
        return $this->createQueryBuilder('mc')
            ->innerJoin('mc.user', 'u')
            ->innerJoin('App\Entity\EventRegistration', 'er', 'WITH', 'er.user = u')
            ->innerJoin('er.event', 'e')
            ->where('e.organizer = :dp')
            ->andWhere('e.startDate >= :today')
            ->setParameter('dp', $dp)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get certificates scheduled for deletion
     */
    public function findScheduledForDeletion(): array
    {
        return $this->createQueryBuilder('mc')
            ->where('mc.scheduledDeletionDate IS NOT NULL')
            ->andWhere('mc.scheduledDeletionDate <= :today')
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getResult();
    }

    /**
     * Count pending certificates (for admin dashboard)
     */
    public function countPending(): int
    {
        return $this->createQueryBuilder('mc')
            ->select('COUNT(mc.id)')
            ->where('mc.status = :pending')
            ->setParameter('pending', MedicalCertificate::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get all certificates for a user (history)
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('mc')
            ->where('mc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('mc.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get certificates expiring soon (for reminders)
     */
    public function findExpiringSoon(int $daysAhead = 30): array
    {
        $futureDate = new \DateTime("+{$daysAhead} days");

        return $this->createQueryBuilder('mc')
            ->where('mc.status = :validated')
            ->andWhere('mc.expiryDate <= :futureDate')
            ->andWhere('mc.expiryDate >= :today')
            ->setParameter('validated', MedicalCertificate::STATUS_VALIDATED)
            ->setParameter('futureDate', $futureDate)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('mc.expiryDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
