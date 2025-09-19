<?php

namespace App\Repository;

use App\Entity\DivingLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DivingLevel>
 */
class DivingLevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DivingLevel::class);
    }

    public function save(DivingLevel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DivingLevel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all diving levels ordered by sortOrder (ascending)
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('dl')
            ->where('dl.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('dl.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a diving level by its code
     */
    public function findByCode(string $code): ?DivingLevel
    {
        return $this->createQueryBuilder('dl')
            ->andWhere('dl.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }
}