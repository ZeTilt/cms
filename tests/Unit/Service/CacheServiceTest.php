<?php

namespace App\Tests\Unit\Service;

use App\Service\CacheService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class CacheServiceTest extends TestCase
{
    private CacheService $cacheService;
    private CacheInterface $cache;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->cacheService = new CacheService($this->cache);
    }

    public function testGetCachedBlogArticlesCallsCallbackOnMiss(): void
    {
        $callbackCalled = false;
        $expectedArticles = [
            ['id' => 1, 'title' => 'Article 1'],
            ['id' => 2, 'title' => 'Article 2']
        ];

        $callback = function() use (&$callbackCalled, $expectedArticles) {
            $callbackCalled = true;
            return $expectedArticles;
        };

        $result = $this->cacheService->getCachedBlogArticles(1, 10, $callback);

        $this->assertTrue($callbackCalled);
        $this->assertSame($expectedArticles, $result);
    }

    public function testGetCachedBlogArticlesReturnsCachedData(): void
    {
        $expectedArticles = [
            ['id' => 1, 'title' => 'Cached Article']
        ];

        // First call to populate cache
        $this->cacheService->getCachedBlogArticles(1, 10, function() use ($expectedArticles) {
            return $expectedArticles;
        });

        $callbackCalled = false;
        $callback = function() use (&$callbackCalled) {
            $callbackCalled = true;
            return ['should not be called'];
        };

        // Second call should use cache
        $result = $this->cacheService->getCachedBlogArticles(1, 10, $callback);

        $this->assertFalse($callbackCalled);
        $this->assertSame($expectedArticles, $result);
    }

    public function testGetCachedBlogArticlesWithDifferentParameters(): void
    {
        $articles1 = [['id' => 1, 'title' => 'Page 1 Article']];
        $articles2 = [['id' => 2, 'title' => 'Page 2 Article']];

        // Cache different pages
        $result1 = $this->cacheService->getCachedBlogArticles(1, 10, function() use ($articles1) {
            return $articles1;
        });

        $result2 = $this->cacheService->getCachedBlogArticles(2, 10, function() use ($articles2) {
            return $articles2;
        });

        $this->assertSame($articles1, $result1);
        $this->assertSame($articles2, $result2);
        $this->assertNotSame($result1, $result2);
    }

    public function testGetCachedArticleBySlug(): void
    {
        $expectedArticle = (object) ['id' => 1, 'title' => 'Test Article', 'slug' => 'test-article'];

        $callback = function() use ($expectedArticle) {
            return $expectedArticle;
        };

        $result = $this->cacheService->getCachedArticle('test-article', $callback);

        $this->assertEquals($expectedArticle, $result);
    }

    public function testGetCachedArticleReturnsCachedData(): void
    {
        $expectedArticle = (object) ['id' => 1, 'title' => 'Cached Article'];

        // First call to populate cache
        $this->cacheService->getCachedArticle('cached-article', function() use ($expectedArticle) {
            return $expectedArticle;
        });

        $callbackCalled = false;
        $callback = function() use (&$callbackCalled) {
            $callbackCalled = true;
            return (object) ['should not be called'];
        };

        // Second call should use cache
        $result = $this->cacheService->getCachedArticle('cached-article', $callback);

        $this->assertFalse($callbackCalled);
        $this->assertEquals($expectedArticle, $result);
    }

    public function testGetCachedArticleHandlesNullReturn(): void
    {
        $callback = function() {
            return null;
        };

        $result = $this->cacheService->getCachedArticle('non-existent', $callback);

        $this->assertNull($result);
    }

    public function testClearBlogCacheRemovesArticlesCache(): void
    {
        // Populate cache
        $articles = [['id' => 1, 'title' => 'Article']];
        $this->cacheService->getCachedBlogArticles(1, 10, function() use ($articles) {
            return $articles;
        });

        // Clear cache
        $this->cacheService->clearBlogCache();

        // Should call callback again after clear
        $callbackCalled = false;
        $this->cacheService->getCachedBlogArticles(1, 10, function() use (&$callbackCalled, $articles) {
            $callbackCalled = true;
            return $articles;
        });

        $this->assertTrue($callbackCalled);
    }

    public function testClearArticleCacheRemovesSpecificArticle(): void
    {
        // Populate cache for two articles
        $this->cacheService->getCachedArticle('article-1', function() {
            return (object) ['id' => 1, 'title' => 'Article 1'];
        });

        $this->cacheService->getCachedArticle('article-2', function() {
            return (object) ['id' => 2, 'title' => 'Article 2'];
        });

        // Clear only article-1
        $this->cacheService->clearArticleCache('article-1');

        // article-1 should call callback again
        $callback1Called = false;
        $this->cacheService->getCachedArticle('article-1', function() use (&$callback1Called) {
            $callback1Called = true;
            return (object) ['id' => 1, 'title' => 'Article 1 Updated'];
        });

        // article-2 should still be cached
        $callback2Called = false;
        $this->cacheService->getCachedArticle('article-2', function() use (&$callback2Called) {
            $callback2Called = true;
            return (object) ['should not be called'];
        });

        $this->assertTrue($callback1Called);
        $this->assertFalse($callback2Called);
    }

    public function testGetCachedCategoriesWithTtl(): void
    {
        $expectedCategories = ['tech', 'design', 'business'];

        $callback = function() use ($expectedCategories) {
            return $expectedCategories;
        };

        $result = $this->cacheService->getCachedCategories($callback);

        $this->assertSame($expectedCategories, $result);
    }

    public function testCacheKeyGeneration(): void
    {
        // Test that different parameters generate different cache keys
        $this->cacheService->getCachedBlogArticles(1, 5, function() {
            return ['page1-limit5'];
        });

        $this->cacheService->getCachedBlogArticles(1, 10, function() {
            return ['page1-limit10'];
        });

        $this->cacheService->getCachedBlogArticles(2, 5, function() {
            return ['page2-limit5'];
        });

        // Each should have been cached independently
        $result1 = $this->cacheService->getCachedBlogArticles(1, 5, function() {
            return ['should not be called'];
        });

        $result2 = $this->cacheService->getCachedBlogArticles(1, 10, function() {
            return ['should not be called'];
        });

        $this->assertSame(['page1-limit5'], $result1);
        $this->assertSame(['page1-limit10'], $result2);
    }
}