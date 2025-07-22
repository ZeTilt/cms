<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Page;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PageTest extends TestCase
{
    private Page $page;
    private User $user;

    protected function setUp(): void
    {
        $this->page = new Page();
        $this->user = new User();
        $this->user->setFirstName('John')->setLastName('Doe');
    }

    public function testPageCreation(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->page->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->page->getUpdatedAt());
        $this->assertEquals('draft', $this->page->getStatus());
        $this->assertEquals('page', $this->page->getType());
        $this->assertEmpty($this->page->getTags());
    }

    public function testBasicProperties(): void
    {
        $this->page->setTitle('Test Page');
        $this->page->setContent('<p>Test content</p>');
        $this->page->setExcerpt('Test excerpt');
        $this->page->setAuthor($this->user);

        $this->assertEquals('Test Page', $this->page->getTitle());
        $this->assertEquals('test-page', $this->page->getSlug());
        $this->assertEquals('<p>Test content</p>', $this->page->getContent());
        $this->assertEquals('Test excerpt', $this->page->getExcerpt());
        $this->assertEquals($this->user, $this->page->getAuthor());
    }

    public function testSlugGeneration(): void
    {
        $this->page->setTitle('Complex Title! With Special @#$% Characters');
        $this->assertEquals('complex-title-with-special-characters', $this->page->getSlug());
        
        // Test manual slug setting
        $this->page->setSlug('custom-slug');
        $this->assertEquals('custom-slug', $this->page->getSlug());
        
        // Test slug generation after title change
        $this->page->setTitle('New Title');
        $this->assertEquals('new-title', $this->page->getSlug());
    }

    public function testSEOProperties(): void
    {
        $this->page->setMetaTitle('Custom Meta Title');
        $this->page->setMetaDescription('Custom meta description');

        $this->assertEquals('Custom Meta Title', $this->page->getMetaTitle());
        $this->assertEquals('Custom meta description', $this->page->getMetaDescription());
    }

    public function testTagManagement(): void
    {
        $tags = ['tag1', 'tag2', 'tag3'];
        $this->page->setTags($tags);
        
        $this->assertEquals($tags, $this->page->getTags());
        
        // Test empty tags
        $this->page->setTags([]);
        $this->assertEmpty($this->page->getTags());
    }

    public function testTypeManagement(): void
    {
        $this->assertEquals('page', $this->page->getType());
        
        $this->page->setType('blog');
        $this->assertEquals('blog', $this->page->getType());
        
        $this->assertTrue($this->page->isBlog());
        $this->assertFalse($this->page->isStaticPage());
        
        $this->page->setType('page');
        $this->assertFalse($this->page->isBlogPost());
        $this->assertTrue($this->page->isStaticPage());
    }

    public function testStatusManagement(): void
    {
        $this->assertEquals('draft', $this->page->getStatus());
        $this->assertFalse($this->page->isPublished());
        
        $this->page->setStatus('published');
        $this->assertEquals('published', $this->page->getStatus());
        $this->assertTrue($this->page->isPublished());
        
        $this->page->setStatus('archived');
        $this->assertFalse($this->page->isPublished());
    }

    public function testPublishMethod(): void
    {
        $this->assertNull($this->page->getPublishedAt());
        
        $result = $this->page->publish();
        
        $this->assertEquals('published', $this->page->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->page->getPublishedAt());
        $this->assertSame($this->page, $result); // Test fluent interface
    }

    public function testFeaturedImage(): void
    {
        $this->assertNull($this->page->getFeaturedImage());
        
        $this->page->setFeaturedImage('/path/to/image.jpg');
        $this->assertEquals('/path/to/image.jpg', $this->page->getFeaturedImage());
    }

    public function testPreUpdateCallback(): void
    {
        $originalUpdatedAt = $this->page->getUpdatedAt();
        
        // Simulate PreUpdate event
        $this->page->preUpdate();
        
        $this->assertNotEquals($originalUpdatedAt, $this->page->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->page->getUpdatedAt());
    }

    public function testPublishedAtForBlogPosts(): void
    {
        $this->page->setType('blog');
        $publishedDate = new \DateTimeImmutable('2024-01-01');
        
        $this->page->setPublishedAt($publishedDate);
        $this->assertEquals($publishedDate, $this->page->getPublishedAt());
    }

    public function testCompleteWorkflow(): void
    {
        // Create a complete page
        $this->page->setTitle('My Blog Post')
                  ->setContent('<h1>Hello World</h1><p>This is my first post.</p>')
                  ->setExcerpt('My first blog post excerpt')
                  ->setType('blog')
                  ->setTags(['introduction', 'blog'])
                  ->setMetaTitle('My First Blog Post - SEO Title')
                  ->setMetaDescription('An amazing first blog post about getting started')
                  ->setAuthor($this->user)
                  ->publish();

        // Test all properties are set correctly
        $this->assertEquals('My Blog Post', $this->page->getTitle());
        $this->assertEquals('my-blog-post', $this->page->getSlug());
        $this->assertTrue($this->page->isBlog());
        $this->assertTrue($this->page->isPublished());
        $this->assertEquals('published', $this->page->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->page->getPublishedAt());
        $this->assertEquals(['introduction', 'blog'], $this->page->getTags());
        $this->assertEquals($this->user, $this->page->getAuthor());
    }
}