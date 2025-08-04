<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\User;
use App\Service\EavService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private EavService $eavService
    ) {
        parent::__construct($registry, Article::class);
    }

    public function save(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find articles by author
     */
    public function findByAuthor(User $author, array $orderBy = ['created_at' => 'DESC'], int $limit = null, int $offset = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.author = :author')
            ->setParameter('author', $author);

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy('a.' . $field, $direction);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count articles by author
     */
    public function countByAuthor(User $author): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.author = :author')
            ->setParameter('author', $author)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find published articles
     */
    public function findPublished(int $limit = null, int $offset = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.published_at IS NOT NULL')
            ->andWhere('a.published_at <= :now')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTime())
            ->orderBy('a.published_at', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count published articles
     */
    public function countPublished(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.status = :status')
            ->andWhere('a.published_at IS NOT NULL')
            ->andWhere('a.published_at <= :now')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find published articles by category
     */
    public function findPublishedByCategory(string $category, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.published_at IS NOT NULL')
            ->andWhere('a.published_at <= :now')
            ->andWhere('a.category = :category')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTime())
            ->setParameter('category', $category)
            ->orderBy('a.published_at', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find published articles by tag
     */
    public function findPublishedByTag(string $tag, int $limit = null): array
    {
        // Utiliser EAV pour trouver les articles avec le tag
        $articleIds = $this->eavService->findEntitiesByAttribute(
            'Article',
            'tags',
            json_encode([$tag]) // Rechercher dans le JSON
        );

        if (!empty($articleIds)) {
            // Utiliser les IDs trouvés via EAV
            $qb = $this->createQueryBuilder('a')
                ->where('a.status = :status')
                ->andWhere('a.published_at IS NOT NULL')
                ->andWhere('a.published_at <= :now')
                ->andWhere('a.id IN (:ids)')
                ->setParameter('status', 'published')
                ->setParameter('now', new \DateTime())
                ->setParameter('ids', $articleIds)
                ->orderBy('a.published_at', 'DESC');
        } else {
            // Fallback vers la recherche JSON pour les données non migrées
            $qb = $this->createQueryBuilder('a')
                ->where('a.status = :status')
                ->andWhere('a.published_at IS NOT NULL')
                ->andWhere('a.published_at <= :now')
                ->andWhere('a.tags LIKE :tag')
                ->setParameter('status', 'published')
                ->setParameter('now', new \DateTime())
                ->setParameter('tag', '%"' . $tag . '"%')
                ->orderBy('a.published_at', 'DESC');
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Search published articles
     */
    public function searchPublished(string $query, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.published_at IS NOT NULL')
            ->andWhere('a.published_at <= :now')
            ->andWhere('a.title LIKE :query OR a.content LIKE :query OR a.excerpt LIKE :query')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTime())
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.published_at', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all categories used in published articles
     */
    public function getPublishedCategories(): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('DISTINCT a.category')
            ->where('a.status = :status')
            ->andWhere('a.published_at IS NOT NULL')
            ->andWhere('a.published_at <= :now')
            ->andWhere('a.category IS NOT NULL')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTime())
            ->orderBy('a.category', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'category');
    }

    /**
     * Get all tags used in published articles
     */
    public function getPublishedTags(): array
    {
        $articles = $this->findPublished();
        $allTags = [];

        foreach ($articles as $article) {
            if ($article->getTags()) {
                $allTags = array_merge($allTags, $article->getTags());
            }
        }

        return array_unique($allTags);
    }

    /**
     * Count articles by status
     */
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find all articles for admin (no filtering by author)
     */
    public function findAllForAdmin(array $orderBy = ['created_at' => 'DESC'], int $limit = null, int $offset = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.author', 'author')
            ->addSelect('author');

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy('a.' . $field, $direction);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count all articles for admin
     */
    public function countAllForAdmin(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}