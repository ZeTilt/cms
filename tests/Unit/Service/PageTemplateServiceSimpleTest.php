<?php

namespace App\Tests\Unit\Service;

use App\Entity\Page;
use App\Entity\User;
use App\Service\PageTemplateService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class PageTemplateServiceSimpleTest extends TestCase
{
    private PageTemplateService $service;
    private string $testProjectDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->testProjectDir = sys_get_temp_dir() . '/test_templates_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->testProjectDir . '/templates/pages');
        
        $this->service = new PageTemplateService($this->testProjectDir);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testProjectDir)) {
            $this->filesystem->remove($this->testProjectDir);
        }
    }

    public function testCreateTemplateGeneratesFile(): void
    {
        $user = new User();
        $user->setEmail('admin@test.com')->setPassword('password')->setFirstName('Admin')->setLastName('User');
        
        $page = new Page();
        $page->setTitle('Test Page')
             ->setSlug('test-page')
             ->setStatus('draft')
             ->setAuthor($user);

        $templatePath = $this->service->createTemplate($page);
        
        $this->assertSame('test-page.html.twig', $templatePath);
        $this->assertTrue($this->service->templateExists('test-page.html.twig'));
        
        // Verify template file exists
        $fullPath = $this->testProjectDir . '/templates/pages/test-page.html.twig';
        $this->assertFileExists($fullPath);
        
        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('{% extends \'base.html.twig\' %}', $content);
        $this->assertStringContainsString('Test Page', $content);
    }

    public function testTemplateExistsReturnsFalseForNonExistent(): void
    {
        $this->assertFalse($this->service->templateExists('non-existent.html.twig'));
    }

    public function testGetTemplatePathReturnsCorrectPath(): void
    {
        $expectedPath = 'pages/test.html.twig';
        $actualPath = $this->service->getTemplatePath('test.html.twig');
        
        $this->assertSame($expectedPath, $actualPath);
    }

    public function testDeleteTemplateRemovesFile(): void
    {
        // First create a template
        $user = new User();
        $user->setEmail('admin@test.com')->setPassword('password')->setFirstName('Admin')->setLastName('User');
        
        $page = new Page();
        $page->setTitle('Delete Me')
             ->setSlug('delete-me')
             ->setStatus('draft')
             ->setAuthor($user);

        $this->service->createTemplate($page);
        $this->assertTrue($this->service->templateExists('delete-me.html.twig'));
        
        // Now delete it
        $result = $this->service->deleteTemplate('delete-me.html.twig');
        
        $this->assertTrue($result);
        $this->assertFalse($this->service->templateExists('delete-me.html.twig'));
    }

    public function testDeleteNonExistentTemplateReturnsFalse(): void
    {
        $result = $this->service->deleteTemplate('non-existent.html.twig');
        $this->assertFalse($result);
    }

    public function testServiceCreatesTemplatesDirectory(): void
    {
        // Test that the service creates the templates directory
        $newTestDir = sys_get_temp_dir() . '/test_templates_new_' . uniqid();
        $newService = new PageTemplateService($newTestDir);
        
        $this->assertTrue(is_dir($newTestDir . '/templates/pages'));
        
        // Cleanup
        (new Filesystem())->remove($newTestDir);
    }

    public function testCreateTemplateWithSpecialCharacters(): void
    {
        $user = new User();
        $user->setEmail('admin@test.com')->setPassword('password')->setFirstName('Admin')->setLastName('User');
        
        $page = new Page();
        $page->setTitle('Àccénts & Spéciàl Chàrs!')
             ->setSlug('accents-special-chars')
             ->setStatus('published')
             ->setAuthor($user);

        $templatePath = $this->service->createTemplate($page);
        
        $this->assertSame('accents-special-chars.html.twig', $templatePath);
        $this->assertTrue($this->service->templateExists('accents-special-chars.html.twig'));
    }
}