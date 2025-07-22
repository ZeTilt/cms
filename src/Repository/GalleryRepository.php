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
}