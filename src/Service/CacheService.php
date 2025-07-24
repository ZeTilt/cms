<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheService
{
    private const CACHE_TTL = 3600; // 1 hour
    
    public function __construct(
        private CacheInterface $cache
    ) {}

    /**
     * Cache blog articles list
     */
    public function getCachedBlogArticles(int $page, int $limit, callable $callback): array
    {
        $cacheKey = "blog_articles_page_{$page}_limit_{$limit}";
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(self::CACHE_TTL);
            return $callback();
        });
    }

    /**
     * Cache single article
     */
    public function getCachedArticle(string $slug, callable $callback): ?object
    {
        $cacheKey = "article_" . md5($slug);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(self::CACHE_TTL);
            return $callback();
        });
    }

    /**
     * Cache categories list
     */
    public function getCachedCategories(callable $callback): array
    {
        return $this->cache->get('blog_categories', function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(self::CACHE_TTL * 6); // 6 hours for categories
            return $callback();
        });
    }

    /**
     * Cache tags list
     */
    public function getCachedTags(callable $callback): array
    {
        return $this->cache->get('blog_tags', function (ItemInterface $item) use ($callback) {
            $item->expiresAfter(self::CACHE_TTL * 6); // 6 hours for tags
            return $callback();
        });
    }

    /**
     * Clear blog-related caches
     */
    public function clearBlogCache(): void
    {
        // Clear articles cache
        $this->cache->delete('blog_categories');
        $this->cache->delete('blog_tags');
        
        // Clear paginated articles cache (this is basic, in production you'd want more sophisticated cache tagging)
        for ($page = 1; $page <= 50; $page++) { // Clear first 50 pages
            for ($limit = 5; $limit <= 20; $limit += 5) {
                $this->cache->delete("blog_articles_page_{$page}_limit_{$limit}");
            }
        }
    }

    /**
     * Clear cache for specific article
     */
    public function clearArticleCache(string $slug): void
    {
        $cacheKey = "article_" . md5($slug);
        $this->cache->delete($cacheKey);
    }

    /**
     * Warm up cache for critical data
     */
    public function warmupBlogCache(callable $articlesCallback, callable $categoriesCallback, callable $tagsCallback): void
    {
        // Warm up first page of articles
        $this->getCachedBlogArticles(1, 6, $articlesCallback);
        
        // Warm up categories and tags
        $this->getCachedCategories($categoriesCallback);
        $this->getCachedTags($tagsCallback);
    }
}