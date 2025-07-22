<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Gallery;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicGalleryControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private User $testUser;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        
        // Create test user
        $this->testUser = new User();
        $this->testUser->setEmail('public-test@example.com')
                      ->setFirstName('Test')
                      ->setLastName('User');

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    public function testPublicGalleryList(): void
    {
        // Create test galleries
        $publicGallery = new Gallery();
        $publicGallery->setTitle('Public Gallery')
                     ->setDescription('A public gallery for testing')
                     ->setVisibility('public')
                     ->setAuthor($this->testUser);

        $privateGallery = new Gallery();
        $privateGallery->setTitle('Private Gallery')
                      ->setDescription('A private gallery for testing')
                      ->setVisibility('private')
                      ->setAuthor($this->testUser);

        $this->entityManager->persist($publicGallery);
        $this->entityManager->persist($privateGallery);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/galleries');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Photo Galleries');
        
        // Should show public gallery
        $this->assertSelectorTextContains('a', 'Public Gallery');
        
        // Should NOT show private gallery
        $this->assertSelectorTextNotContains('body', 'Private Gallery');
    }

    public function testPublicGalleryShow(): void
    {
        $gallery = new Gallery();
        $gallery->setTitle('Test Public Gallery')
               ->setDescription('Description for public gallery')
               ->setVisibility('public')
               ->setAuthor($this->testUser);

        $this->entityManager->persist($gallery);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/gallery/' . $gallery->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Test Public Gallery');
        $this->assertSelectorTextContains('p', 'Description for public gallery');
    }

    public function testPrivateGalleryAccessWithoutCode(): void
    {
        $gallery = new Gallery();
        $gallery->setTitle('Private Test Gallery')
               ->setVisibility('private')
               ->setAccessCode('secret123')
               ->setAuthor($this->testUser);

        $this->entityManager->persist($gallery);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/gallery/' . $gallery->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Private Gallery');
        $this->assertSelectorExists('input[name="code"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    public function testPrivateGalleryAccessWithCorrectCode(): void
    {
        $gallery = new Gallery();
        $gallery->setTitle('Private Test Gallery')
               ->setDescription('Private gallery description')
               ->setVisibility('private')
               ->setAccessCode('secret123')
               ->setAuthor($this->testUser);

        $this->entityManager->persist($gallery);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/gallery/' . $gallery->getSlug() . '?code=secret123');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Private Test Gallery');
        $this->assertSelectorTextContains('p', 'Private gallery description');
    }

    public function testPrivateGalleryAccessWithWrongCode(): void
    {
        $gallery = new Gallery();
        $gallery->setTitle('Private Test Gallery')
               ->setVisibility('private')
               ->setAccessCode('secret123')
               ->setAuthor($this->testUser);

        $this->entityManager->persist($gallery);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/gallery/' . $gallery->getSlug() . '?code=wrongcode');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Private Gallery');
        $this->assertSelectorExists('input[name="code"]');
        $this->assertSelectorTextContains('body', 'Invalid access code');
    }

    public function testGalleryNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/gallery/non-existent-gallery');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEmptyGalleryList(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/galleries');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Photo Galleries');
        $this->assertSelectorTextContains('h3', 'No galleries yet');
        $this->assertSelectorTextContains('p', 'Check back soon');
    }

    public function testGalleryListMetadata(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/galleries');

        $this->assertSelectorTextContains('title', 'Photo Galleries - ZeTilt CMS');
        $this->assertSelectorExists('nav');
        $this->assertSelectorExists('footer');
    }

    public function testGalleryNavigation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/galleries');

        // Check navigation links
        $this->assertSelectorExists('a[href="/"]'); // Home link
        $this->assertSelectorExists('a[href*="galleries"]'); // Galleries link
        $this->assertSelectorExists('a[href*="blog"]'); // Blog link
        $this->assertSelectorExists('a[href*="login"]'); // Admin login
    }

    protected function tearDown(): void
    {
        // Clean up galleries
        $galleries = $this->entityManager->getRepository(Gallery::class)
            ->findBy(['author' => $this->testUser]);
        
        foreach ($galleries as $gallery) {
            $this->entityManager->remove($gallery);
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