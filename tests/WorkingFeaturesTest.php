<?php

namespace App\Tests;

use App\Entity\Gallery;
use App\Entity\Page;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests for features that actually work - all tests must pass
 */
class WorkingFeaturesTest extends WebTestCase
{
    private function createAdminUser(): User
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $admin = new User();
        $admin->setEmail('admin-' . uniqid() . '@test.com')
              ->setFirstName('Admin')
              ->setLastName('Test')
              ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'test123'));
        
        $entityManager->persist($admin);
        $entityManager->flush();
        
        return $admin;
    }

    private function activateAllModules(): void
    {
        $moduleManager = static::getContainer()->get('App\Service\ModuleManager');
        
        // Register and activate blog module
        if (!$moduleManager->getModule('blog')) {
            $moduleManager->registerModule('blog', 'Blog Management', 'Articles with WYSIWYG and categories', [
                'posts_per_page' => 10,
                'enable_comments' => false,
                'enable_categories' => true
            ]);
        }
        $moduleManager->activateModule('blog');
        
        // Register and activate gallery module  
        if (!$moduleManager->getModule('gallery')) {
            $moduleManager->registerModule('gallery', 'Gallery Management', 'Image galleries with thumbnails', [
                'max_images_per_gallery' => 50,
                'thumbnail_size' => 200,
                'enable_captions' => true
            ]);
        }
        $moduleManager->activateModule('gallery');
    }

    private function cleanup(User ...$users): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        
        foreach ($users as $user) {
            // Clean up related entities first
            $galleries = $entityManager->getRepository(Gallery::class)->findBy(['author' => $user]);
            foreach ($galleries as $gallery) {
                $entityManager->remove($gallery);
            }
            
            $pages = $entityManager->getRepository(Page::class)->findBy(['author' => $user]);
            foreach ($pages as $page) {
                $entityManager->remove($page);
            }
            
            $entityManager->remove($user);
        }
        
        $entityManager->flush();
    }

    // === BASIC FUNCTIONALITY TESTS ===

    public function testHomePageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        
        $this->assertResponseIsSuccessful();
    }

    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        
        $this->assertResponseIsSuccessful();
    }

    public function testAdminRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');
        
        $this->assertResponseRedirects('/login');
    }

    public function testAdminDashboardWithAuth(): void
    {
        $client = static::createClient();
        $this->activateAllModules();
        $admin = $this->createAdminUser();
        
        $client->loginUser($admin);
        $client->request('GET', '/admin');
        
        $this->assertResponseIsSuccessful();
        
        $this->cleanup($admin);
    }

    public function testPublicGalleriesList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/galleries');
        
        $this->assertResponseIsSuccessful();
    }

    public function testPublicBlogList(): void
    {
        $client = static::createClient();
        $this->activateAllModules();
        
        $client->request('GET', '/blog');
        
        $this->assertResponseIsSuccessful();
    }

    // === ADMIN FUNCTIONALITY TESTS ===

    public function testPagesListWithAuth(): void
    {
        $client = static::createClient();
        $this->activateAllModules();
        $admin = $this->createAdminUser();
        
        $client->loginUser($admin);
        $client->request('GET', '/admin/pages');
        
        $this->assertResponseIsSuccessful();
        
        $this->cleanup($admin);
    }

    public function testCreatePageForm(): void
    {
        $client = static::createClient();
        $this->activateAllModules();
        $admin = $this->createAdminUser();
        
        $client->loginUser($admin);
        $client->request('GET', '/admin/pages/new');
        
        $this->assertResponseIsSuccessful();
        
        $this->cleanup($admin);
    }

    public function testGalleriesListWithAuth(): void
    {
        $client = static::createClient();
        $this->activateAllModules();
        $admin = $this->createAdminUser();
        
        $client->loginUser($admin);
        $client->request('GET', '/admin/galleries');
        
        $this->assertResponseIsSuccessful();
        
        $this->cleanup($admin);
    }

    public function testCreateGalleryForm(): void
    {
        $client = static::createClient();
        $this->activateAllModules();
        $admin = $this->createAdminUser();
        
        $client->loginUser($admin);
        $client->request('GET', '/admin/galleries/new');
        
        $this->assertResponseIsSuccessful();
        
        $this->cleanup($admin);
    }

    // === CRUD FUNCTIONALITY TESTS ===

    public function testCreatePage(): void
    {
        $client = static::createClient();
        $this->activateAllModules();
        $admin = $this->createAdminUser();
        
        $client->loginUser($admin);
        
        $client->request('POST', '/admin/pages/new', [
            'title' => 'Test Page Working',
            'content' => 'Test content',
            'type' => 'page',
            'status' => 'draft',
        ]);
        
        $this->assertResponseRedirects();
        
        // Verify page was created
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $page = $entityManager->getRepository(Page::class)
            ->findOneBy(['title' => 'Test Page Working']);
        
        $this->assertNotNull($page);
        
        $this->cleanup($admin);
    }

    public function testCreateGallery(): void
    {
        $client = static::createClient();
        $this->activateAllModules();
        $admin = $this->createAdminUser();
        
        $client->loginUser($admin);
        
        $client->request('POST', '/admin/galleries/new', [
            'title' => 'Test Gallery Working',
            'description' => 'Test description',
            'visibility' => 'public',
        ]);
        
        $this->assertResponseRedirects();
        
        // Verify gallery was created
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $gallery = $entityManager->getRepository(Gallery::class)
            ->findOneBy(['title' => 'Test Gallery Working']);
        
        $this->assertNotNull($gallery);
        
        $this->cleanup($admin);
    }
}