<?php

namespace App\EventListener;

use App\Entity\Article;
use App\Service\CacheService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Article::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Article::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: Article::class)]
class ArticleCacheListener
{
    public function __construct(
        private CacheService $cacheService
    ) {}

    public function postPersist(Article $article, LifecycleEventArgs $args): void
    {
        $this->clearRelevantCache($article);
    }

    public function postUpdate(Article $article, LifecycleEventArgs $args): void
    {
        $this->clearRelevantCache($article);
    }

    public function postRemove(Article $article, LifecycleEventArgs $args): void
    {
        $this->clearRelevantCache($article);
    }

    private function clearRelevantCache(Article $article): void
    {
        // Clear general blog cache
        $this->cacheService->clearBlogCache();
        
        // Clear specific article cache
        if ($article->getSlug()) {
            $this->cacheService->clearArticleCache($article->getSlug());
        }
    }
}