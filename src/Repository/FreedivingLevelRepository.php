<?php

namespace App\Repository;

use App\Entity\FreedivingLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FreedivingLevel>
 */
class FreedivingLevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FreedivingLevel::class);
    }

    public function save(FreedivingLevel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FreedivingLevel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all freediving levels ordered by sortOrder (ascending)
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('fl')
            ->where('fl.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('fl.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a freediving level by its code
     */
    public function findByCode(string $code): ?FreedivingLevel
    {
        return $this->createQueryBuilder('fl')
            ->andWhere('fl.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all active freediving levels
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('fl')
            ->where('fl.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('fl.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
