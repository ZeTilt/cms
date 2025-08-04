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
     * Trouve tous les attributs pour une entité donnée
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
     * Trouve un attribut spécifique pour une entité
     */
    public function findOneByEntityAndAttribute(string $entityType, int $entityId, string $attributeName): ?EntityAttribute
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
     * Trouve toutes les entités ayant un attribut avec une valeur donnée
     */
    public function findEntitiesByAttributeValue(string $entityType, string $attributeName, string $attributeValue): array
    {
        return $this->createQueryBuilder('ea')
            ->select('ea.entityId')
            ->where('ea.entityType = :entityType')
            ->andWhere('ea.attributeName = :attributeName')
            ->andWhere('ea.attributeValue = :attributeValue')
            ->setParameter('entityType', $entityType)
            ->setParameter('attributeName', $attributeName)
            ->setParameter('attributeValue', $attributeValue)
            ->getQuery()
            ->getScalarResult();
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

    /**
     * Obtient tous les noms d'attributs uniques pour un type d'entité
     */
    public function getAttributeNames(string $entityType): array
    {
        $result = $this->createQueryBuilder('ea')
            ->select('DISTINCT ea.attributeName')
            ->where('ea.entityType = :entityType')
            ->setParameter('entityType', $entityType)
            ->orderBy('ea.attributeName', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'attributeName');
    }

    /**
     * Obtient des statistiques sur les attributs par type d'entité
     */
    public function getAttributeStats(string $entityType): array
    {
        return $this->createQueryBuilder('ea')
            ->select('ea.attributeName', 'ea.attributeType', 'COUNT(ea.id) as usageCount')
            ->where('ea.entityType = :entityType')
            ->setParameter('entityType', $entityType)
            ->groupBy('ea.attributeName', 'ea.attributeType')
            ->orderBy('usageCount', 'DESC')
            ->getQuery()
            ->getResult();
    }
}