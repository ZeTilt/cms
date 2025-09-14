<?php

namespace App\Repository;

use App\Entity\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Page>
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    /**
     * Find published blog articles
     */
    public function findPublishedBlogArticles(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.type = :type')
            ->andWhere('p.status = :status')
            ->setParameter('type', 'blog')
            ->setParameter('status', 'published')
            ->orderBy('p.publishedAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find published pages
     */
    public function findPublishedPages(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.type = :type')
            ->andWhere('p.status = :status')
            ->setParameter('type', 'page')
            ->setParameter('status', 'published')
            ->orderBy('p.sortOrder', 'ASC')
            ->addOrderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find page by slug
     */
    public function findPublishedPageBySlug(string $slug): ?Page
    {
        return $this->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->andWhere('p.status = :status')
            ->setParameter('slug', $slug)
            ->setParameter('status', 'published')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find articles by tag
     */
    public function findPublishedArticlesByTag(string $tag): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.type = :type')
            ->andWhere('p.status = :status')
            ->andWhere('JSON_CONTAINS(p.tags, :tag) = 1')
            ->setParameter('type', 'blog')
            ->setParameter('status', 'published')
            ->setParameter('tag', json_encode($tag))
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}