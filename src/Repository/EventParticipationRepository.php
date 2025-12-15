<?php

namespace App\Repository;

use App\Entity\EventParticipation;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventParticipation>
 */
class EventParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventParticipation::class);
    }

    public function save(EventParticipation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EventParticipation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByEventAndUser(Event $event, User $user): ?EventParticipation
    {
        return $this->createQueryBuilder('ep')
            ->andWhere('ep.event = :event')
            ->andWhere('ep.participant = :user')
            ->setParameter('event', $event)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveParticipationsByEvent(Event $event): array
    {
        return $this->createQueryBuilder('ep')
            ->andWhere('ep.event = :event')
            ->andWhere('ep.status IN (:activeStatuses)')
            ->andWhere('ep.isWaitingList = :notWaitingList')
            ->setParameter('event', $event)
            ->setParameter('activeStatuses', ['registered', 'confirmed'])
            ->setParameter('notWaitingList', false)
            ->getQuery()
            ->getResult();
    }

    public function countActiveParticipationsByEvent(Event $event): int
    {
        return $this->createQueryBuilder('ep')
            ->select('COUNT(ep.id)')
            ->andWhere('ep.event = :event')
            ->andWhere('ep.status IN (:activeStatuses)')
            ->andWhere('ep.isWaitingList = :notWaitingList')
            ->setParameter('event', $event)
            ->setParameter('activeStatuses', ['registered', 'confirmed'])
            ->setParameter('notWaitingList', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('ep')
            ->andWhere('ep.participant = :user')
            ->orderBy('ep.registrationDate', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active participations for a user (for CACI access control)
     */
    public function findActiveParticipationsForUser(User $user): array
    {
        return $this->createQueryBuilder('ep')
            ->innerJoin('ep.event', 'e')
            ->andWhere('ep.participant = :user')
            ->andWhere('ep.status IN (:activeStatuses)')
            ->andWhere('e.startDate >= :today')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', ['registered', 'confirmed'])
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getResult();
    }
}