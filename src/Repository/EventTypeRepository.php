<?php

namespace App\Repository;

use App\Entity\EventType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventType>
 */
class EventTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventType::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('et')
            ->andWhere('et.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('et.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code): ?EventType
    {
        return $this->createQueryBuilder('et')
            ->andWhere('et.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }
}