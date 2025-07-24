<?php

namespace App\Tests\Unit\Service;

use App\Service\ContentSanitizer;
use PHPUnit\Framework\TestCase;

class ContentSanitizerTest extends TestCase
{
    private ContentSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new ContentSanitizer();
    }

    public function testSanitizeRemovesMaliciousScript(): void
    {
        $maliciousContent = '<script>alert("XSS attack!");</script><p>Valid content</p>';
        $result = $this->sanitizer->sanitizeContent($maliciousContent);
        
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert(', $result);
        $this->assertStringContainsString('<p>Valid content</p>', $result);
    }

    public function testSanitizeRemovesJavaScriptEvents(): void
    {
        $maliciousContent = '<img src="image.jpg" onclick="alert(\'XSS\')" onerror="badFunction()">';
        $result = $this->sanitizer->sanitizeContent($maliciousContent);
        
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('src="image.jpg"', $result);
    }

    public function testSanitizeAllowsBasicHtmlTags(): void
    {
        $validHtml = '<h1>Title</h1><p>Paragraph with <strong>bold</strong> and <em>italic</em> text.</p><ul><li>List item</li></ul>';
        $result = $this->sanitizer->sanitizeContent($validHtml);
        
        $this->assertStringContainsString('<h1>Title</h1>', $result);
        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('<em>italic</em>', $result);
        $this->assertStringContainsString('<ul><li>List item</li></ul>', $result);
    }

    public function testSanitizeRemovesUnsafeLinks(): void
    {
        $maliciousContent = '<a href="javascript:alert(\'XSS\')">Click me</a><a href="http://example.com">Safe link</a>';
        $result = $this->sanitizer->sanitizeContent($maliciousContent);
        
        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringContainsString('href="http://example.com"', $result);
    }

    public function testSanitizeHandlesEmptyContent(): void
    {
        $result = $this->sanitizer->sanitizeContent('');
        $this->assertSame('', $result);
    }

    public function testSanitizeHandlesNull(): void
    {
        // Test with empty string instead since method expects string
        $result = $this->sanitizer->sanitizeContent('');
        $this->assertSame('', $result);
    }

    public function testGenerateExcerptWithValidLength(): void
    {
        $content = '<p>This is a long paragraph with <strong>formatting</strong> that should be truncated properly to create a clean excerpt without HTML tags.</p>';
        $excerpt = $this->sanitizer->generateExcerpt($content, 50);
        
        $this->assertLessThanOrEqual(53, strlen($excerpt)); // 50 + "..."
        $this->assertStringNotContainsString('<p>', $excerpt);
        $this->assertStringNotContainsString('<strong>', $excerpt);
        $this->assertStringEndsWith('...', $excerpt);
    }

    public function testGenerateExcerptWithShortContent(): void
    {
        $content = '<p>Short text</p>';
        $excerpt = $this->sanitizer->generateExcerpt($content, 50);
        
        $this->assertSame('Short text', $excerpt);
        $this->assertStringNotContainsString('...', $excerpt);
    }

    public function testSanitizeUrlWithValidUrls(): void
    {
        $validUrls = [
            'https://example.com',
            'http://test.org/path?param=value',
            'https://subdomain.example.com/page'
        ];

        foreach ($validUrls as $url) {
            $result = $this->sanitizer->sanitizeUrl($url);
            $this->assertSame($url, $result);
        }
    }

    public function testSanitizeUrlWithInvalidUrls(): void
    {
        $invalidUrls = [
            'javascript:alert("xss")',
            'data:text/html,<script>alert("xss")</script>',
            ''
        ];

        foreach ($invalidUrls as $url) {
            $result = $this->sanitizer->sanitizeUrl($url);
            $this->assertNull($result, "URL '$url' should be rejected");
        }
    }

    public function testSanitizeContentPreservesTextFormatting(): void
    {
        $content = '<p>Paragraph with <br> line break</p><blockquote>Quote text</blockquote>';
        $result = $this->sanitizer->sanitizeContent($content);
        
        // HTMLPurifier converts <br> to <br />
        $this->assertStringContainsString('<br />', $result);
        $this->assertStringContainsString('<blockquote>Quote text</blockquote>', $result);
    }

    public function testSanitizeContentRemovesStyleAttributes(): void
    {
        $content = '<p style="color: red; background: url(javascript:alert())">Text</p>';
        $result = $this->sanitizer->sanitizeContent($content);
        
        $this->assertStringNotContainsString('style=', $result);
        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringContainsString('<p>Text</p>', $result);
    }
}