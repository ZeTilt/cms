<?php

namespace App\Repository;

use App\Entity\EntityAttribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EntityAttribute>
 */
class EntityAttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntityAttribute::class);
    }

    /**
     * Trouve tous les attributs d'une entité spécifique
     */
    public function findByEntity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('ea')
            ->where('ea.entityType = :entityType')
            ->andWhere('ea.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('ea.attributeName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un attribut spécifique d'une entité
     */
    public function findOneByEntityAttribute(string $entityType, int $entityId, string $attributeName): ?EntityAttribute
    {
        return $this->createQueryBuilder('ea')
            ->where('ea.entityType = :entityType')
            ->andWhere('ea.entityId = :entityId')
            ->andWhere('ea.attributeName = :attributeName')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->setParameter('attributeName', $attributeName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère toutes les valeurs uniques pour un attribut donné
     */
    public function findDistinctValues(string $entityType, string $attributeName): array
    {
        $result = $this->createQueryBuilder('ea')
            ->select('DISTINCT ea.attributeValue')
            ->where('ea.entityType = :entityType')
            ->andWhere('ea.attributeName = :attributeName')
            ->andWhere('ea.attributeValue IS NOT NULL')
            ->setParameter('entityType', $entityType)
            ->setParameter('attributeName', $attributeName)
            ->orderBy('ea.attributeValue', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'attributeValue');
    }

    /**
     * Compte le nombre d'entités ayant un attribut avec une valeur donnée
     */
    public function countByValue(string $entityType, string $attributeName, string $value): int
    {
        return $this->createQueryBuilder('ea')
            ->select('COUNT(ea.id)')
            ->where('ea.entityType = :entityType')
            ->andWhere('ea.attributeName = :attributeName')
            ->andWhere('ea.attributeValue = :value')
            ->setParameter('entityType', $entityType)
            ->setParameter('attributeName', $attributeName)
            ->setParameter('value', $value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Supprime tous les attributs d'une entité
     */
    public function deleteByEntity(string $entityType, int $entityId): int
    {
        return $this->createQueryBuilder('ea')
            ->delete()
            ->where('ea.entityType = :entityType')
            ->andWhere('ea.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->execute();
    }
}