<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\Functional\WebTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    use WebTestTrait;

    public function testDashboardRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseRedirects('/login');
    }

    public function testDashboardWithAdmin(): void
    {
        $client = static::createClient();
        $adminUser = $this->createTestUser('admin@test.com', ['ROLE_ADMIN']);
        $client->loginUser($adminUser);
        
        $crawler = $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dashboard');

        $this->cleanupUser($adminUser);
    }

    public function testDashboardRoute(): void
    {
        $client = static::createClient();
        $adminUser = $this->createTestUser('admin2@test.com', ['ROLE_ADMIN']);
        $client->loginUser($adminUser);
        
        $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dashboard');

        $this->cleanupUser($adminUser);
    }

    public function testModulesPageRequiresSuperAdmin(): void
    {
        $client = static::createClient();
        $adminUser = $this->createTestUser('admin3@test.com', ['ROLE_ADMIN']);
        $client->loginUser($adminUser); // Regular admin, not super admin
        
        $client->request('GET', '/admin/modules');

        $this->assertResponseStatusCodeSame(403); // Forbidden

        $this->cleanupUser($adminUser);
    }

    public function testModulesPageWithSuperAdmin(): void
    {
        $client = static::createClient();
        $superAdminUser = $this->createTestUser('superadmin@test.com', ['ROLE_SUPER_ADMIN']);
        $client->loginUser($superAdminUser);
        
        $crawler = $client->request('GET', '/admin/modules');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modules');

        $this->cleanupUser($superAdminUser);
    }

    public function testDashboardShowsActiveModules(): void
    {
        $client = static::createClient();
        $adminUser = $this->createTestUser('admin4@test.com', ['ROLE_ADMIN']);
        $client->loginUser($adminUser);
        
        $crawler = $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        
        // Check that modules are displayed
        $this->assertSelectorTextContains('body', 'Pages');
        $this->assertSelectorTextContains('body', 'Gallery');

        $this->cleanupUser($adminUser);
    }

    public function testDashboardNavigation(): void
    {
        $client = static::createClient();
        $adminUser = $this->createTestUser('admin5@test.com', ['ROLE_ADMIN']);
        $client->loginUser($adminUser);
        
        $crawler = $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        
        // Check navigation elements
        $this->assertSelectorExists('nav, .navigation, [class*="nav"]');
        $this->assertSelectorExists('a[href*="logout"]');

        $this->cleanupUser($adminUser);
    }

    public function testDashboardMetadata(): void
    {
        $client = static::createClient();
        $adminUser = $this->createTestUser('admin6@test.com', ['ROLE_ADMIN']);
        $client->loginUser($adminUser);
        
        $crawler = $client->request('GET', '/admin');

        $this->assertSelectorTextContains('title', 'Dashboard');
        $this->assertSelectorExists('header, .header, main, .main');

        $this->cleanupUser($adminUser);
    }
}