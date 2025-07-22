<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Gallery;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GalleryControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private User $testUser;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        
        // Create test user
        $this->testUser = new User();
        $this->testUser->setEmail('gallery-test@example.com')
                      ->setFirstName('Test')
                      ->setLastName('User')
                      ->setRoles(['ROLE_ADMIN']);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->testUser->setPassword($passwordHasher->hashPassword($this->testUser, 'password123'));

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    public function testGalleryListRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/galleries');

        $this->assertResponseRedirects();
        $this->assertResponseHeaderContains('location', '/login');
    }

    public function testGalleryListWhenAuthenticated(): void
    {
        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $crawler = $client->request('GET', '/admin/galleries');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Galleries');
        $this->assertSelectorExists('a[href*="galleries/new"]');
    }

    public function testCreateGalleryForm(): void
    {
        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $crawler = $client->request('GET', '/admin/galleries/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'New Gallery');
        $this->assertSelectorExists('input[name="title"]');
        $this->assertSelectorExists('textarea[name="description"]');
        $this->assertSelectorExists('input[name="visibility"]');
    }

    public function testCreateGallerySubmission(): void
    {
        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $crawler = $client->request('GET', '/admin/galleries/new');
        
        $form = $crawler->selectButton('Create Gallery')->form([
            'title' => 'Test Gallery',
            'description' => 'This is a test gallery',
            'visibility' => 'public',
        ]);

        $client->submit($form);

        // Should redirect to gallery show page
        $this->assertResponseRedirects();
        
        // Follow redirect
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Test Gallery');

        // Verify gallery was created in database
        $gallery = $this->entityManager->getRepository(Gallery::class)
            ->findOneBy(['title' => 'Test Gallery']);
        
        $this->assertNotNull($gallery);
        $this->assertEquals('test-gallery', $gallery->getSlug());
        $this->assertEquals('public', $gallery->getVisibility());
        $this->assertEquals($this->testUser, $gallery->getAuthor());
    }

    public function testCreatePrivateGalleryWithAccessCode(): void
    {
        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $crawler = $client->request('GET', '/admin/galleries/new');
        
        $form = $crawler->selectButton('Create Gallery')->form([
            'title' => 'Private Test Gallery',
            'description' => 'This is a private test gallery',
            'visibility' => 'private',
            'access_code' => 'secret123',
        ]);

        $client->submit($form);
        $client->followRedirect();

        // Verify private gallery was created
        $gallery = $this->entityManager->getRepository(Gallery::class)
            ->findOneBy(['title' => 'Private Test Gallery']);
        
        $this->assertNotNull($gallery);
        $this->assertEquals('private', $gallery->getVisibility());
        $this->assertEquals('secret123', $gallery->getAccessCode());
        $this->assertTrue($gallery->requiresAccessCode());
    }

    public function testEditGallery(): void
    {
        // Create a gallery first
        $gallery = new Gallery();
        $gallery->setTitle('Original Title')
               ->setDescription('Original description')
               ->setVisibility('public')
               ->setAuthor($this->testUser);
        
        $this->entityManager->persist($gallery);
        $this->entityManager->flush();

        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $crawler = $client->request('GET', '/admin/galleries/' . $gallery->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Edit Gallery');
        
        $form = $crawler->selectButton('Update Gallery')->form([
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'visibility' => 'private',
            'access_code' => 'newSecret456',
        ]);

        $client->submit($form);
        $client->followRedirect();

        // Verify changes
        $this->entityManager->refresh($gallery);
        $this->assertEquals('Updated Title', $gallery->getTitle());
        $this->assertEquals('updated-title', $gallery->getSlug());
        $this->assertEquals('Updated description', $gallery->getDescription());
        $this->assertEquals('private', $gallery->getVisibility());
        $this->assertEquals('newSecret456', $gallery->getAccessCode());
    }

    public function testDeleteGallery(): void
    {
        // Create a gallery
        $gallery = new Gallery();
        $gallery->setTitle('Gallery to Delete')
               ->setAuthor($this->testUser);
        
        $this->entityManager->persist($gallery);
        $this->entityManager->flush();
        $galleryId = $gallery->getId();

        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        // Submit delete form
        $client->request('POST', '/admin/galleries/' . $galleryId . '/delete', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete_gallery'),
        ]);

        $this->assertResponseRedirects('/admin/galleries');

        // Verify gallery was deleted
        $deletedGallery = $this->entityManager->getRepository(Gallery::class)->find($galleryId);
        $this->assertNull($deletedGallery);
    }

    public function testGalleryFormValidation(): void
    {
        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $crawler = $client->request('GET', '/admin/galleries/new');
        
        // Submit form with missing required field
        $form = $crawler->selectButton('Create Gallery')->form([
            'title' => '', // Empty title should cause validation error
            'description' => 'Description without title',
        ]);

        $client->submit($form);

        // Should not redirect (stays on form with errors)
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert, .error, [class*="error"]');
    }

    public function testShowGallery(): void
    {
        // Create a gallery
        $gallery = new Gallery();
        $gallery->setTitle('Test Gallery Show')
               ->setDescription('Gallery for show test')
               ->setAuthor($this->testUser);
        
        $this->entityManager->persist($gallery);
        $this->entityManager->flush();

        $client = static::createClient();
        $client->loginUser($this->testUser);
        
        $crawler = $client->request('GET', '/admin/galleries/' . $gallery->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Test Gallery Show');
        $this->assertSelectorTextContains('p', 'Gallery for show test');
        $this->assertSelectorExists('input[type="file"]'); // Upload interface
        $this->assertSelectorExists('a[href*="edit"]'); // Edit button
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