<?php

namespace App\Repository;

use App\Entity\Module;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Module>
 */
class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    /**
     * @return Module[] Returns an array of Module objects
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Module[] Returns an array of active Module objects
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.active = :active')
            ->setParameter('active', true)
            ->orderBy('m.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $name): ?Module
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}