<?php

namespace App\Tests\Unit\Service;

use App\Service\ArticleValidator;
use App\Service\ContentSanitizer;
use App\Repository\ArticleRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ArticleValidatorSimpleTest extends TestCase
{
    private ArticleValidator $validator;
    private MockObject $articleRepository;
    private MockObject $contentSanitizer;

    protected function setUp(): void
    {
        $this->articleRepository = $this->createMock(ArticleRepository::class);
        $this->contentSanitizer = $this->createMock(ContentSanitizer::class);
        $this->validator = new ArticleValidator($this->articleRepository, $this->contentSanitizer);
    }

    public function testValidateWithValidData(): void
    {
        $data = [
            'title' => 'Valid Article Title',
            'slug' => 'valid-article-title',
            'content' => '<p>This is valid content with proper HTML that is long enough.</p>',
            'excerpt' => 'Valid excerpt',
            'status' => 'draft',
            'category' => 'Technology',
            'tags' => ['php', 'symfony'],
            'meta_description' => 'Test meta description'
        ];

        $errors = $this->validator->validate($data);
        
        $this->assertEmpty($errors, 'Valid data should not produce errors');
    }

    public function testValidateDetectsEmptyTitle(): void
    {
        $data = [
            'title' => '',
            'slug' => 'valid-slug',
            'content' => '<p>Valid content that is long enough.</p>',
            'status' => 'draft'
        ];

        $errors = $this->validator->validate($data);
        
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('title', $errors);
    }

    public function testValidateWorksWithDifferentSlugFormats(): void
    {
        // Test that validator accepts various slug formats
        $slugFormats = [
            'valid-slug',
            'another-valid-slug-123',
            'simple'
        ];
        
        foreach ($slugFormats as $slug) {
            $data = [
                'title' => 'Valid Title',
                'slug' => $slug,
                'content' => '<p>Valid content that is long enough.</p>',
                'status' => 'draft'
            ];

            $errors = $this->validator->validate($data);
            
            // Should not have slug-specific errors
            $this->assertArrayNotHasKey('slug', $errors, "Slug '$slug' should be valid");
        }
    }

    public function testValidateDetectsEmptyContent(): void
    {
        $data = [
            'title' => 'Valid Title',
            'slug' => 'valid-slug',
            'content' => '',
            'status' => 'draft'
        ];

        $errors = $this->validator->validate($data);
        
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('content', $errors);
    }

    public function testValidateDetectsInvalidStatus(): void
    {
        $data = [
            'title' => 'Valid Title',
            'slug' => 'valid-slug', 
            'content' => '<p>Valid content that is long enough.</p>',
            'status' => 'invalid_status'
        ];

        $errors = $this->validator->validate($data);
        
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('status', $errors);
    }

    public function testValidateAcceptsValidStatuses(): void
    {
        $validStatuses = ['draft', 'published'];
        
        foreach ($validStatuses as $status) {
            $data = [
                'title' => 'Valid Title',
                'slug' => 'valid-slug',
                'content' => '<p>Valid content that is long enough.</p>',
                'status' => $status
            ];

            $errors = $this->validator->validate($data);
            
            $this->assertArrayNotHasKey('status', $errors, "Status '$status' should be valid");
        }
    }

    public function testValidateReturnsSameErrorsOnMultipleCallsWithSameData(): void
    {
        $data = [
            'title' => '',
            'slug' => 'Invalid Slug',
            'content' => '',
            'status' => 'invalid'
        ];

        $errors1 = $this->validator->validate($data);
        $errors2 = $this->validator->validate($data);
        
        $this->assertEquals($errors1, $errors2);
        $this->assertNotEmpty($errors1);
    }
}