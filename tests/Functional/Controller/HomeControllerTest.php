<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomepage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'ZeTilt CMS');
        $this->assertSelectorExists('img[alt="ZeTilt"]');
        $this->assertSelectorTextContains('p', 'A modular, business-focused content management system built with Symfony');
    }

    public function testHomepageLinks(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        // Test main navigation links exist
        $this->assertSelectorExists('a[href*="galleries"]');
        $this->assertSelectorExists('a[href*="blog"]');
        $this->assertSelectorExists('a[href*="login"]');
        
        // Test link text
        $this->assertSelectorTextContains('a', 'View Galleries');
        $this->assertSelectorTextContains('a', 'Read Blog');
        $this->assertSelectorTextContains('a', 'Admin Login');
    }

    public function testHomepageResponseHeaders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/html; charset=UTF-8');
    }

    public function testHomepageMetadata(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        // Check page title
        $this->assertSelectorTextContains('title', 'Welcome - ZeTilt CMS');
        
        // Check that page has proper structure
        $this->assertSelectorExists('html[lang]');
        $this->assertSelectorExists('meta[charset]');
        $this->assertSelectorExists('meta[name="viewport"]');
    }
}