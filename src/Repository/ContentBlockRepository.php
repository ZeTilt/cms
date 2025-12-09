<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\ContentBlock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContentBlock>
 */
class ContentBlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentBlock::class);
    }

    /**
     * Find all blocks for an article, ordered by position
     *
     * @return ContentBlock[]
     */
    public function findByArticle(Article $article): array
    {
        return $this->createQueryBuilder('cb')
            ->where('cb.article = :article')
            ->setParameter('article', $article)
            ->orderBy('cb.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the next position for a new block in an article
     */
    public function getNextPosition(Article $article): int
    {
        $result = $this->createQueryBuilder('cb')
            ->select('MAX(cb.position)')
            ->where('cb.article = :article')
            ->setParameter('article', $article)
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? -1) + 1;
    }

    /**
     * Reorder blocks for an article
     *
     * @param array $blockIds Array of block IDs in the new order
     */
    public function reorderBlocks(Article $article, array $blockIds): void
    {
        $em = $this->getEntityManager();

        foreach ($blockIds as $position => $blockId) {
            $em->createQueryBuilder()
                ->update(ContentBlock::class, 'cb')
                ->set('cb.position', ':position')
                ->where('cb.id = :id')
                ->andWhere('cb.article = :article')
                ->setParameter('position', $position)
                ->setParameter('id', $blockId)
                ->setParameter('article', $article)
                ->getQuery()
                ->execute();
        }
    }

    /**
     * Delete all blocks for an article
     */
    public function deleteByArticle(Article $article): int
    {
        return $this->createQueryBuilder('cb')
            ->delete()
            ->where('cb.article = :article')
            ->setParameter('article', $article)
            ->getQuery()
            ->execute();
    }
}
