<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Page;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicPagesControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private User $testUser;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        
        // Create test user
        $this->testUser = new User();
        $this->testUser->setEmail('public-pages-test@example.com')
                      ->setFirstName('Test')
                      ->setLastName('User');

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    public function testBlogListEmpty(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/blog');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Blog');
        $this->assertSelectorTextContains('body', 'No blog posts');
    }

    public function testBlogListWithPosts(): void
    {
        // Create published blog posts
        $publishedPost = new Page();
        $publishedPost->setTitle('Published Blog Post')
                     ->setContent('This is a published blog post')
                     ->setType('blog')
                     ->setStatus('published')
                     ->setPublishedAt(new \DateTimeImmutable())
                     ->setAuthor($this->testUser);

        $draftPost = new Page();
        $draftPost->setTitle('Draft Blog Post')
                 ->setContent('This is a draft blog post')
                 ->setType('blog')
                 ->setStatus('draft')
                 ->setAuthor($this->testUser);

        $this->entityManager->persist($publishedPost);
        $this->entityManager->persist($draftPost);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/blog');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Blog');
        
        // Should show published post
        $this->assertSelectorTextContains('a', 'Published Blog Post');
        
        // Should NOT show draft post
        $this->assertSelectorTextNotContains('body', 'Draft Blog Post');
    }

    public function testBlogPostShow(): void
    {
        $blogPost = new Page();
        $blogPost->setTitle('Test Blog Post')
                 ->setContent('This is the content of the blog post')
                 ->setType('blog')
                 ->setStatus('published')
                 ->setPublishedAt(new \DateTimeImmutable())
                 ->setAuthor($this->testUser);

        $this->entityManager->persist($blogPost);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/blog/' . $blogPost->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Test Blog Post');
        $this->assertSelectorTextContains('body', 'This is the content of the blog post');
    }

    public function testBlogPostNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/blog/non-existent-post');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDraftBlogPostNotAccessible(): void
    {
        $draftPost = new Page();
        $draftPost->setTitle('Draft Post')
                 ->setContent('This is a draft')
                 ->setType('blog')
                 ->setStatus('draft')
                 ->setAuthor($this->testUser);

        $this->entityManager->persist($draftPost);
        $this->entityManager->flush();

        $client = static::createClient();
        $client->request('GET', '/blog/' . $draftPost->getSlug());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPublicPageShow(): void
    {
        $page = new Page();
        $page->setTitle('About Us')
             ->setContent('This is our about us page')
             ->setType('page')
             ->setStatus('published')
             ->setPublishedAt(new \DateTimeImmutable())
             ->setAuthor($this->testUser);

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/' . $page->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'About Us');
        $this->assertSelectorTextContains('body', 'This is our about us page');
    }

    public function testPublicPageNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/non-existent-page');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDraftPageNotAccessible(): void
    {
        $draftPage = new Page();
        $draftPage->setTitle('Draft Page')
                 ->setContent('This is a draft page')
                 ->setType('page')
                 ->setStatus('draft')
                 ->setAuthor($this->testUser);

        $this->entityManager->persist($draftPage);
        $this->entityManager->flush();

        $client = static::createClient();
        $client->request('GET', '/' . $draftPage->getSlug());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBlogListOrdering(): void
    {
        $now = new \DateTimeImmutable();
        
        // Create posts with different publish dates
        $olderPost = new Page();
        $olderPost->setTitle('Older Post')
                 ->setContent('Older content')
                 ->setType('blog')
                 ->setStatus('published')
                 ->setPublishedAt($now->modify('-2 days'))
                 ->setAuthor($this->testUser);

        $newerPost = new Page();
        $newerPost->setTitle('Newer Post')
                 ->setContent('Newer content')
                 ->setType('blog')
                 ->setStatus('published')
                 ->setPublishedAt($now->modify('-1 day'))
                 ->setAuthor($this->testUser);

        $this->entityManager->persist($olderPost);
        $this->entityManager->persist($newerPost);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/blog');

        $this->assertResponseIsSuccessful();
        
        // Get the page content
        $content = $client->getResponse()->getContent();
        
        // Newer post should appear before older post
        $newerPos = strpos($content, 'Newer Post');
        $olderPos = strpos($content, 'Older Post');
        
        $this->assertNotFalse($newerPos);
        $this->assertNotFalse($olderPos);
        $this->assertLessThan($olderPos, $newerPos);
    }

    public function testBlogPostMetadata(): void
    {
        $blogPost = new Page();
        $blogPost->setTitle('SEO Test Post')
                 ->setContent('Content with SEO')
                 ->setType('blog')
                 ->setStatus('published')
                 ->setMetaTitle('Custom Meta Title')
                 ->setMetaDescription('Custom meta description')
                 ->setPublishedAt(new \DateTimeImmutable())
                 ->setAuthor($this->testUser);

        $this->entityManager->persist($blogPost);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/blog/' . $blogPost->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Custom Meta Title');
        
        // Check for meta description if it exists in the template
        $metaDescription = $crawler->filter('meta[name="description"]');
        if ($metaDescription->count() > 0) {
            $this->assertEquals('Custom meta description', $metaDescription->attr('content'));
        }
    }

    public function testPageWithTags(): void
    {
        $page = new Page();
        $page->setTitle('Tagged Page')
             ->setContent('Page with tags')
             ->setType('page')
             ->setStatus('published')
             ->setTags(['technology', 'web', 'cms'])
             ->setPublishedAt(new \DateTimeImmutable())
             ->setAuthor($this->testUser);

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/' . $page->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Tagged Page');
    }

    public function testBlogListNavigation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/blog');

        $this->assertResponseIsSuccessful();
        
        // Check navigation elements exist
        $this->assertSelectorExists('nav, .navigation, [class*="nav"]');
        $this->assertSelectorExists('a[href="/"]'); // Home link
    }

    public function testPublicPageNavigation(): void
    {
        $page = new Page();
        $page->setTitle('Test Page Nav')
             ->setContent('Navigation test')
             ->setType('page')
             ->setStatus('published')
             ->setPublishedAt(new \DateTimeImmutable())
             ->setAuthor($this->testUser);

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/' . $page->getSlug());

        $this->assertResponseIsSuccessful();
        
        // Check navigation elements
        $this->assertSelectorExists('nav, .navigation, [class*="nav"]');
        $this->assertSelectorExists('a[href="/"]'); // Home link
        $this->assertSelectorExists('a[href*="blog"]'); // Blog link
    }

    protected function tearDown(): void
    {
        // Clean up pages
        $pages = $this->entityManager->getRepository(Page::class)
            ->findBy(['author' => $this->testUser]);
        
        foreach ($pages as $page) {
            $this->entityManager->remove($page);
        }

        // Clean up test user
        if ($this->testUser) {
            $this->entityManager->remove($this->testUser);
        }
        
        $this->entityManager->flush();
        $this->entityManager->close();

        parent::tearDown();
    }
}