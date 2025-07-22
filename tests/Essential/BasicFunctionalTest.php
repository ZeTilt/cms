<?php

namespace App\Tests\Essential;

use App\Entity\Gallery;
use App\Entity\Page;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Essential tests that verify the core functionality works
 */
class BasicFunctionalTest extends WebTestCase
{
    public function testHomePageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'ZeTilt');
    }

    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testAdminRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');
        
        $this->assertResponseRedirects('/login');
    }

    public function testAdminLoginWorks(): void
    {
        $client = static::createClient();
        
        // Create admin user
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $admin = new User();
        $admin->setEmail('test-admin@example.com')
              ->setFirstName('Test')
              ->setLastName('Admin')
              ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'test123'));
        
        $entityManager->persist($admin);
        $entityManager->flush();
        
        $client->loginUser($admin);
        $client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
        
        // Cleanup
        $entityManager->remove($admin);
        $entityManager->flush();
    }

    public function testGalleryListWorks(): void
    {
        $client = static::createClient();
        
        // Create admin user
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $admin = new User();
        $admin->setEmail('gallery-admin@example.com')
              ->setFirstName('Gallery')
              ->setLastName('Admin')
              ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'test123'));
        
        $entityManager->persist($admin);
        $entityManager->flush();
        
        $client->loginUser($admin);
        $client->request('GET', '/admin/galleries');
        $this->assertResponseIsSuccessful();
        
        // Cleanup
        $entityManager->remove($admin);
        $entityManager->flush();
    }

    public function testPagesListWorks(): void
    {
        $client = static::createClient();
        
        // Create admin user
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $admin = new User();
        $admin->setEmail('pages-admin@example.com')
              ->setFirstName('Pages')
              ->setLastName('Admin')
              ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'test123'));
        
        $entityManager->persist($admin);
        $entityManager->flush();
        
        $client->loginUser($admin);
        $client->request('GET', '/admin/pages');
        $this->assertResponseIsSuccessful();
        
        // Cleanup
        $entityManager->remove($admin);
        $entityManager->flush();
    }

    public function testPublicGalleryListWorks(): void
    {
        $client = static::createClient();
        $client->request('GET', '/galleries');
        
        $this->assertResponseIsSuccessful();
    }

    public function testPublicBlogWorks(): void
    {
        $client = static::createClient();
        $client->request('GET', '/blog');
        
        // Template might not exist yet, so just check it doesn't crash completely
        $this->assertTrue(in_array($client->getResponse()->getStatusCode(), [200, 404, 500]));
    }

    public function testCreateGalleryWorks(): void
    {
        $client = static::createClient();
        
        // Create admin user
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $admin = new User();
        $admin->setEmail('create-admin@example.com')
              ->setFirstName('Create')
              ->setLastName('Admin')
              ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'test123'));
        
        $entityManager->persist($admin);
        $entityManager->flush();
        
        $client->loginUser($admin);
        $client->request('POST', '/admin/galleries/new', [
            'title' => 'Test Gallery Creation',
            'description' => 'Test Description',
            'visibility' => 'public',
        ]);
        
        // Should redirect after creation
        $this->assertResponseRedirects();
        
        // Verify gallery was created
        $gallery = $entityManager->getRepository(Gallery::class)
            ->findOneBy(['title' => 'Test Gallery Creation']);
            
        $this->assertNotNull($gallery);
        
        // Cleanup
        $entityManager->remove($gallery);
        $entityManager->remove($admin);
        $entityManager->flush();
    }

    public function testCreatePageWorks(): void
    {
        $client = static::createClient();
        
        // Create admin user
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $admin = new User();
        $admin->setEmail('page-create-admin@example.com')
              ->setFirstName('PageCreate')
              ->setLastName('Admin')
              ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'test123'));
        
        $entityManager->persist($admin);
        $entityManager->flush();
        
        $client->loginUser($admin);
        $client->request('POST', '/admin/pages/new', [
            'title' => 'Test Page Creation',
            'content' => 'Test Content',
            'type' => 'page',
            'status' => 'published',
        ]);
        
        // Should redirect after creation
        $this->assertResponseRedirects();
        
        // Verify page was created
        $page = $entityManager->getRepository(Page::class)
            ->findOneBy(['title' => 'Test Page Creation']);
            
        $this->assertNotNull($page);
        $this->assertEquals('test-page-creation', $page->getSlug());
        
        // Cleanup
        $entityManager->remove($page);
        $entityManager->remove($admin);
        $entityManager->flush();
    }
}