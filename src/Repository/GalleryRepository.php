<?php

namespace App\Repository;

use App\Entity\Gallery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Gallery>
 *
 * @method Gallery|null find($id, $lockMode = null, $lockVersion = null)
 * @method Gallery|null findOneBy(array $criteria, array $orderBy = null)
 * @method Gallery[]    findAll()
 * @method Gallery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GalleryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gallery::class);
    }

    public function findPublicGalleries(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('g')
            ->where('g.visibility = :visibility')
            ->setParameter('visibility', 'public')
            ->orderBy('g.updatedAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByAuthor($author, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('g')
            ->where('g.author = :author')
            ->setParameter('author', $author)
            ->orderBy('g.updatedAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findBySlugAndVisibility(string $slug, string $visibility = 'public'): ?Gallery
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.images', 'i')
            ->addSelect('i')
            ->where('g.slug = :slug')
            ->andWhere('g.visibility = :visibility')
            ->setParameter('slug', $slug)
            ->setParameter('visibility', $visibility)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPublicBySlug(string $slug): ?Gallery
    {
        return $this->findBySlugAndVisibility($slug, 'public');
    }

    public function findBySlug(string $slug): ?Gallery
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.images', 'i')
            ->addSelect('i')
            ->where('g.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByAuthor($author): int
    {
        return $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.author = :author')
            ->setParameter('author', $author)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalImagesCount(): int
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.images', 'i')
            ->select('COUNT(i.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find all galleries for admin (no filtering by author)
     */
    public function findAllForAdmin(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.author', 'author')
            ->addSelect('author')
            ->orderBy('g.updatedAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count all galleries for admin
     */
    public function countAllForAdmin(): int
    {
        return $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Méthodes d'expiration
     */

    /**
     * Trouver toutes les galeries avec une date d'expiration
     */
    public function findGalleriesWithExpiration(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.endDate IS NOT NULL')
            ->orderBy('g.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les galeries qui expirent dans les N prochains jours
     */
    public function findGalleriesExpiringWithin(int $days): array
    {
        $now = new \DateTimeImmutable();
        $futureDate = $now->modify("+{$days} days");

        return $this->createQueryBuilder('g')
            ->where('g.endDate IS NOT NULL')
            ->andWhere('g.endDate <= :futureDate')
            ->andWhere('g.endDate >= :now')
            ->andWhere('g.visibility != :expired')
            ->setParameter('futureDate', $futureDate)
            ->setParameter('now', $now)
            ->setParameter('expired', 'expired')
            ->orderBy('g.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les galeries avec expiration
     */
    public function countGalleriesWithExpiration(): int
    {
        return $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.endDate IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compter les galeries expirées
     */
    public function countExpiredGalleries(): int
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.endDate IS NOT NULL')
            ->andWhere('g.endDate < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouver les galeries expirées depuis longtemps
     */
    public function findOldExpiredGalleries(\DateTimeImmutable $cutoffDate): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.endDate IS NOT NULL')
            ->andWhere('g.endDate < :cutoffDate')
            ->andWhere('g.visibility = :expired')
            ->setParameter('cutoffDate', $cutoffDate)
            ->setParameter('expired', 'expired')
            ->orderBy('g.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les galeries expirées (pour désactivation)
     */
    public function findExpiredGalleries(): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('g')
            ->where('g.endDate IS NOT NULL')
            ->andWhere('g.endDate < :now')
            ->andWhere('g.visibility != :expired')
            ->andWhere('g.visibility != :archived')
            ->setParameter('now', $now)
            ->setParameter('expired', 'expired')
            ->setParameter('archived', 'archived')
            ->orderBy('g.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}