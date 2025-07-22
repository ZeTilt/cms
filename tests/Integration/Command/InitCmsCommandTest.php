<?php

namespace App\Tests\Integration\Command;

use App\Command\InitCmsCommand;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InitCmsCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testExecute(): void
    {
        $application = new Application(self::$kernel);
        $command = $application->find('zetilt:cms:init');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('ZeTilt CMS Initialization', $output);
        $this->assertStringContainsString('Registering Core Modules', $output);
        $this->assertStringContainsString('Pages Module', $output);
        $this->assertStringContainsString('Gallery Module', $output);
        $this->assertStringContainsString('successfully!', $output);

        $this->assertEquals(0, $commandTester->getStatusCode());

        // Verify admin user was created
        $adminUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@zetilt.cms']);
        
        $this->assertNotNull($adminUser);
        $this->assertEquals('Super', $adminUser->getFirstName());
        $this->assertEquals('Admin', $adminUser->getLastName());
        $this->assertContains('ROLE_SUPER_ADMIN', $adminUser->getRoles());
    }

    public function testExecuteWhenUserExists(): void
    {
        // Run command once to create user
        $application = new Application(self::$kernel);
        $command = $application->find('zetilt:cms:init');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Run again to test existing user scenario
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Super admin user already exists', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $adminUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@zetilt.cms']);
        
        if ($adminUser) {
            $this->entityManager->remove($adminUser);
            $this->entityManager->flush();
        }

        parent::tearDown();
        $this->entityManager->close();
    }
}