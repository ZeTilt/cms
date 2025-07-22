<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Page;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PagesControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private User $testUser;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        
        // Create test user
        $this->testUser = new User();
        $this->testUser->setEmail('pages-test@example.com')
                      ->setFirstName('Test')
                      ->setLastName('User')
                      ->setRoles(['ROLE_ADMIN']);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->testUser->setPassword($passwordHasher->hashPassword($this->testUser, 'password123'));

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    public function testPageListRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/pages');

        $this->assertResponseRedirects();
        $this->assertResponseHeaderContains('location', '/login');
    }

    public function testPageListWhenAuthenticated(): void
    {
        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $crawler = $client->request('GET', '/admin/pages');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Pages');
        $this->assertSelectorExists('a[href*="pages/new"]');
    }

    public function testCreatePageForm(): void
    {
        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $crawler = $client->request('GET', '/admin/pages/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Create New Page');
        $this->assertSelectorExists('input[name="title"]');
        $this->assertSelectorExists('textarea[name="content"], #content');
        $this->assertSelectorExists('select[name="type"], input[name="type"]');
        $this->assertSelectorExists('select[name="status"], input[name="status"]');
    }

    public function testCreatePageSubmission(): void
    {
        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $client->request('POST', '/admin/pages/new', [
            'title' => 'Test Page',
            'content' => 'This is test content for the page.',
            'type' => 'page',
            'status' => 'draft',
            'excerpt' => 'Test excerpt',
            'meta_title' => 'Test Meta Title',
            'meta_description' => 'Test meta description',
            'tags' => 'test, page, content',
        ]);

        $this->assertResponseRedirects();
        
        // Verify page was created in database
        $page = $this->entityManager->getRepository(Page::class)
            ->findOneBy(['title' => 'Test Page']);
        
        $this->assertNotNull($page);
        $this->assertEquals('test-page', $page->getSlug());
        $this->assertEquals('page', $page->getType());
        $this->assertEquals('draft', $page->getStatus());
        $this->assertEquals($this->testUser, $page->getAuthor());
        $this->assertEquals(['test', 'page', 'content'], $page->getTags());
    }

    public function testCreateBlogPost(): void
    {
        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $client->request('POST', '/admin/pages/new', [
            'title' => 'Test Blog Post',
            'content' => 'This is a test blog post content.',
            'type' => 'blog',
            'status' => 'published',
        ]);

        $page = $this->entityManager->getRepository(Page::class)
            ->findOneBy(['title' => 'Test Blog Post']);
        
        $this->assertNotNull($page);
        $this->assertEquals('blog', $page->getType());
        $this->assertEquals('published', $page->getStatus());
        $this->assertNotNull($page->getPublishedAt());
    }

    public function testEditPage(): void
    {
        // Create a page first
        $page = new Page();
        $page->setTitle('Original Title')
             ->setContent('Original content')
             ->setType('page')
             ->setStatus('draft')
             ->setAuthor($this->testUser);
        
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $crawler = $client->request('GET', '/admin/pages/' . $page->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Edit Page: Original Title');
        $this->assertInputValueSame('title', 'Original Title');
        
        // Update the page
        $client->request('POST', '/admin/pages/' . $page->getId() . '/edit', [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'type' => 'page',
            'status' => 'published',
            'slug' => 'custom-updated-slug',
        ]);

        $this->assertResponseRedirects();

        // Verify changes
        $this->entityManager->refresh($page);
        $this->assertEquals('Updated Title', $page->getTitle());
        $this->assertEquals('custom-updated-slug', $page->getSlug());
        $this->assertEquals('Updated content', $page->getContent());
        $this->assertEquals('published', $page->getStatus());
        $this->assertNotNull($page->getPublishedAt());
    }

    public function testDeletePage(): void
    {
        // Create a page
        $page = new Page();
        $page->setTitle('Page to Delete')
             ->setContent('Content to delete')
             ->setAuthor($this->testUser);
        
        $this->entityManager->persist($page);
        $this->entityManager->flush();
        $pageId = $page->getId();

        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        // Submit delete request
        $client->request('POST', '/admin/pages/' . $pageId . '/delete');

        $this->assertResponseRedirects('/admin/pages');

        // Verify page was deleted
        $deletedPage = $this->entityManager->getRepository(Page::class)->find($pageId);
        $this->assertNull($deletedPage);
    }

    public function testPublishPage(): void
    {
        // Create a draft page
        $page = new Page();
        $page->setTitle('Draft Page')
             ->setContent('Draft content')
             ->setType('page')
             ->setStatus('draft')
             ->setAuthor($this->testUser);
        
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        // Publish the page
        $client->request('POST', '/admin/pages/' . $page->getId() . '/publish');

        $this->assertResponseRedirects('/admin/pages');

        // Verify page was published
        $this->entityManager->refresh($page);
        $this->assertTrue($page->isPublished());
        $this->assertNotNull($page->getPublishedAt());
    }

    public function testPageFormValidation(): void
    {
        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        // Submit form with missing required fields
        $client->request('POST', '/admin/pages/new', [
            'title' => '', // Empty title should cause validation error
            'content' => '', // Empty content should cause validation error
        ]);

        // Should not redirect (stays on form with errors)
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert, .error, [class*="error"]');
    }

    public function testPageSlugGeneration(): void
    {
        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $client->request('POST', '/admin/pages/new', [
            'title' => 'Complex Title with Spaces & Special Characters!',
            'content' => 'Test content',
            'type' => 'page',
            'status' => 'draft',
            'generate_slug' => '1',
        ]);

        $page = $this->entityManager->getRepository(Page::class)
            ->findOneBy(['title' => 'Complex Title with Spaces & Special Characters!']);
        
        $this->assertNotNull($page);
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $page->getSlug());
        $this->assertStringContainsString('complex', $page->getSlug());
        $this->assertStringContainsString('title', $page->getSlug());
    }

    public function testPagesListDisplay(): void
    {
        // Create test pages
        $page1 = new Page();
        $page1->setTitle('Published Page')
             ->setContent('Published content')
             ->setStatus('published')
             ->setAuthor($this->testUser);

        $page2 = new Page();
        $page2->setTitle('Draft Page')
             ->setContent('Draft content')
             ->setStatus('draft')
             ->setAuthor($this->testUser);

        $this->entityManager->persist($page1);
        $this->entityManager->persist($page2);
        $this->entityManager->flush();

        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $crawler = $client->request('GET', '/admin/pages');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Published Page');
        $this->assertSelectorTextContains('body', 'Draft Page');
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