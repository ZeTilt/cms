<?php

namespace App\Repository;

use App\Entity\Boat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Boat>
 */
class BoatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boat::class);
    }

    /**
     * @return Boat[] Returns an array of active Boat objects
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Boat[] Returns all boats ordered by name
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
