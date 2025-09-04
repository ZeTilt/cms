<?php

namespace App\Repository;

use App\Entity\ProdigiProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProdigiProduct>
 */
class ProdigiProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProdigiProduct::class);
    }

    /**
     * Récupérer tous les produits disponibles
     * @return ProdigiProduct[]
     */
    public function findAllAvailable(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('available', true)
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver un produit par SKU
     */
    public function findOneBySku(string $sku): ?ProdigiProduct
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.sku = :sku')
            ->setParameter('sku', $sku)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupérer les N produits les plus anciens (pour refresh)
     * @return ProdigiProduct[]
     */
    public function findOldestProducts(int $limit = 10, int $maxAgeHours = 24): array
    {
        $maxAge = new \DateTimeImmutable("-{$maxAgeHours} hours");
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.lastUpdatedAt < :maxAge')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('maxAge', $maxAge)
            ->setParameter('available', true)
            ->orderBy('p.lastUpdatedAt', 'ASC') // Les plus anciens d'abord
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les produits par catégorie
     */
    public function countByCategory(): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('p.category, COUNT(p.id) as count')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('available', true)
            ->groupBy('p.category')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['category']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * Récupérer les produits par catégorie
     * @return ProdigiProduct[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('category', $category)
            ->setParameter('available', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques du cache
     */
    public function getCacheStats(): array
    {
        $qb = $this->createQueryBuilder('p');
        
        $totalProducts = $qb->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $availableProducts = $qb->select('COUNT(p.id)')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('available', true)
            ->getQuery()
            ->getSingleScalarResult();

        $maxAge = new \DateTimeImmutable('-24 hours');
        $recentProducts = $qb->select('COUNT(p.id)')
            ->andWhere('p.lastUpdatedAt >= :maxAge')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('maxAge', $maxAge)
            ->setParameter('available', true)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_products' => (int) $totalProducts,
            'available_products' => (int) $availableProducts,
            'recent_products' => (int) $recentProducts,
            'old_products' => (int) $availableProducts - (int) $recentProducts
        ];
    }

    /**
     * Compter le nombre de produits anciens qui ont besoin d'un refresh
     */
    public function countOldProducts(int $maxAgeHours = 24): int
    {
        $maxAge = new \DateTimeImmutable("-{$maxAgeHours} hours");
        
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.lastUpdatedAt < :maxAge')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('maxAge', $maxAge)
            ->setParameter('available', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Nettoyer les produits très anciens (plus de 30 jours sans refresh)
     */
    public function cleanupOldProducts(): int
    {
        $maxAge = new \DateTimeImmutable('-30 days');
        
        return $this->createQueryBuilder('p')
            ->delete()
            ->andWhere('p.lastUpdatedAt < :maxAge')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('maxAge', $maxAge)
            ->setParameter('available', false) // Seulement les indisponibles
            ->getQuery()
            ->execute();
    }
}
