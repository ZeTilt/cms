<?php

namespace App\Repository;

use App\Entity\Widget;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Widget>
 */
class WidgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Widget::class);
    }

    /**
     * Trouve tous les widgets actifs
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.active = :active')
            ->setParameter('active', true)
            ->orderBy('w.category', 'ASC')
            ->addOrderBy('w.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les widgets par catégorie
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.category = :category')
            ->andWhere('w.active = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('w.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un widget par son nom
     */
    public function findActiveByName(string $name): ?Widget
    {
        return $this->createQueryBuilder('w')
            ->where('w.name = :name')
            ->andWhere('w.active = :active')
            ->setParameter('name', $name)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les widgets par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.type = :type')
            ->andWhere('w.active = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('w.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans les widgets
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.title LIKE :query OR w.description LIKE :query OR w.name LIKE :query')
            ->andWhere('w.active = :active')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('active', true)
            ->orderBy('w.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtient les statistiques des widgets
     */
    public function getStats(): array
    {
        $total = $this->count([]);
        $active = $this->count(['active' => true]);
        
        $byType = $this->createQueryBuilder('w')
            ->select('w.type, COUNT(w.id) as count')
            ->groupBy('w.type')
            ->getQuery()
            ->getResult();

        $byCategory = $this->createQueryBuilder('w')
            ->select('w.category, COUNT(w.id) as count')
            ->where('w.category IS NOT NULL')
            ->groupBy('w.category')
            ->getQuery()
            ->getResult();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'byType' => array_column($byType, 'count', 'type'),
            'byCategory' => array_column($byCategory, 'count', 'category')
        ];
    }

    /**
     * Vérifie si un nom de widget existe déjà
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.name = :name')
            ->setParameter('name', $name);

        if ($excludeId) {
            $qb->andWhere('w.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
}