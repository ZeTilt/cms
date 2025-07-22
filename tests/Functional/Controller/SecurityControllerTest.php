<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testLoginPageAccess(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Sign in');
        $this->assertSelectorExists('input[name="email"]');
        $this->assertSelectorExists('input[name="password"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    public function testLoginForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/login');
        
        // Follow redirect to see error
        $client->followRedirect();
        $this->assertSelectorExists('.alert, .error, [class*="error"]');
    }

    public function testSuccessfulLogin(): void
    {
        // Create a test user
        $user = new User();
        $user->setEmail('admin@test.com')
             ->setFirstName('Admin')
             ->setLastName('Test')
             ->setRoles(['ROLE_ADMIN']);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/admin/dashboard');
    }

    public function testLogout(): void
    {
        $client = static::createClient();
        $client->request('GET', '/logout');
        
        // Logout should redirect to home or login page
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    public function testAdminRedirectWhenNotLoggedIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/dashboard');
        
        // Should redirect to login
        $this->assertResponseRedirects();
        $this->assertResponseHeaderContains('location', '/login');
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }

        parent::tearDown();
        $this->entityManager->close();
    }
}