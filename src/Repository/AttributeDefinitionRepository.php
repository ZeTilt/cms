<?php

namespace App\Repository;

use App\Entity\AttributeDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AttributeDefinition>
 */
class AttributeDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttributeDefinition::class);
    }

    public function findActiveByEntityType(string $entityType): array
    {
        return $this->createQueryBuilder('ad')
            ->where('ad.entityType = :entityType')
            ->andWhere('ad.active = :active')
            ->setParameter('entityType', $entityType)
            ->setParameter('active', true)
            ->orderBy('ad.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByNameAndEntityType(string $name, string $entityType): ?AttributeDefinition
    {
        return $this->createQueryBuilder('ad')
            ->where('ad.name = :name')
            ->andWhere('ad.entityType = :entityType')
            ->setParameter('name', $name)
            ->setParameter('entityType', $entityType)
            ->getQuery()
            ->getOneOrNullResult();
    }
}