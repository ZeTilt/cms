<?php

namespace App\Repository;

use App\Entity\EventCondition;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventCondition>
 */
class EventConditionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventCondition::class);
    }

    public function save(EventCondition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EventCondition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveByEvent(Event $event): array
    {
        return $this->createQueryBuilder('ec')
            ->andWhere('ec.event = :event')
            ->andWhere('ec.isActive = true')
            ->setParameter('event', $event)
            ->orderBy('ec.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}