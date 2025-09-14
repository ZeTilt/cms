<?php

namespace App\Service;

use App\Entity\Page;
use Symfony\Component\Filesystem\Filesystem;

class PageTemplateService
{
    private string $templatesDir;
    private Filesystem $filesystem;

    public function __construct(string $projectDir)
    {
        $this->templatesDir = $projectDir . '/templates/pages';
        $this->filesystem = new Filesystem();

        // Ensure pages directory exists
        if (!$this->filesystem->exists($this->templatesDir)) {
            $this->filesystem->mkdir($this->templatesDir);
        }
    }

    /**
     * Create a template file for a page
     */
    public function createTemplate(Page $page): string
    {
        $templatePath = $this->generateTemplatePath($page->getSlug());
        $fullPath = $this->templatesDir . '/' . $templatePath;

        // Create directory if needed
        $dir = dirname($fullPath);
        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir);
        }

        // Create template file if it doesn't exist
        if (!$this->filesystem->exists($fullPath)) {
            $content = $this->generateTemplateContent($page);
            $this->filesystem->dumpFile($fullPath, $content);
        }

        return $templatePath;
    }

    /**
     * Check if template file exists
     */
    public function templateExists(string $templatePath): bool
    {
        $fullPath = $this->templatesDir . '/' . $templatePath;
        return $this->filesystem->exists($fullPath);
    }

    /**
     * Get template file path
     */
    public function getTemplatePath(string $templatePath): string
    {
        return 'pages/' . $templatePath;
    }

    /**
     * List all available page templates
     */
    public function getAvailableTemplates(): array
    {
        $templates = [];

        if (!$this->filesystem->exists($this->templatesDir)) {
            return $templates;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->templatesDir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'twig') {
                $relativePath = str_replace($this->templatesDir . '/', '', $file->getPathname());
                $templates[] = $relativePath;
            }
        }

        sort($templates);
        return $templates;
    }

    /**
     * Generate template path from slug
     */
    private function generateTemplatePath(string $slug): string
    {
        return $slug . '.html.twig';
    }

    /**
     * Generate initial template content
     */
    private function generateTemplateContent(Page $page): string
    {
        $title = $page->getTitle();
        $slug = $page->getSlug();

        return <<<TWIG
{% extends 'base.html.twig' %}

{% block title %}{$title} - ZeTilt CMS{% endblock %}

{% block body %}
<div class="bg-white py-24 sm:py-32">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl lg:mx-0">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                {$title}
            </h1>

            {# Add your page content here #}
            <div class="mt-8 prose prose-lg max-w-none">
                <p class="text-xl leading-8 text-gray-600">
                    Welcome to the {$title} page. Edit this template at <code>templates/pages/{$slug}.html.twig</code> to customize the content.
                </p>

                {# Example sections you can add:

                <section class="mt-16">
                    <h2>Section Title</h2>
                    <p>Section content...</p>
                </section>

                <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-2">
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3>Feature 1</h3>
                        <p>Description...</p>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3>Feature 2</h3>
                        <p>Description...</p>
                    </div>
                </div>

                #}
            </div>
        </div>
    </div>
</div>
{% endblock %}
TWIG;
    }

    /**
     * Delete template file
     */
    public function deleteTemplate(string $templatePath): bool
    {
        $fullPath = $this->templatesDir . '/' . $templatePath;

        if ($this->filesystem->exists($fullPath)) {
            $this->filesystem->remove($fullPath);
            return true;
        }

        return false;
    }

    /**
     * Rename template file
     */
    public function renameTemplate(string $oldPath, string $newPath): bool
    {
        $oldFullPath = $this->templatesDir . '/' . $oldPath;
        $newFullPath = $this->templatesDir . '/' . $newPath;

        if ($this->filesystem->exists($oldFullPath) && !$this->filesystem->exists($newFullPath)) {
            // Create directory if needed
            $dir = dirname($newFullPath);
            if (!$this->filesystem->exists($dir)) {
                $this->filesystem->mkdir($dir);
            }

            $this->filesystem->rename($oldFullPath, $newFullPath);
            return true;
        }

        return false;
    }

    /**
     * Get template file content for editing
     */
    public function getTemplateContent(string $templatePath): ?string
    {
        $fullPath = $this->templatesDir . '/' . $templatePath;

        if ($this->filesystem->exists($fullPath)) {
            return file_get_contents($fullPath);
        }

        return null;
    }

    /**
     * Update template file content
     */
    public function updateTemplateContent(string $templatePath, string $content): bool
    {
        $fullPath = $this->templatesDir . '/' . $templatePath;

        try {
            $this->filesystem->dumpFile($fullPath, $content);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
