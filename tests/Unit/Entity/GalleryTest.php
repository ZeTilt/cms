<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Gallery;
use App\Entity\Image;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class GalleryTest extends TestCase
{
    private Gallery $gallery;
    private User $user;

    protected function setUp(): void
    {
        $this->gallery = new Gallery();
        $this->user = new User();
        $this->user->setFirstName('John')->setLastName('Doe');
    }

    public function testGalleryCreation(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->gallery->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->gallery->getUpdatedAt());
        $this->assertEquals('public', $this->gallery->getVisibility());
        $this->assertCount(0, $this->gallery->getImages());
        $this->assertTrue($this->gallery->isPublic());
        $this->assertFalse($this->gallery->isPrivate());
        $this->assertFalse($this->gallery->requiresAccessCode());
    }

    public function testBasicProperties(): void
    {
        $this->gallery->setTitle('Test Gallery');
        $this->gallery->setDescription('A test gallery description');
        $this->gallery->setAuthor($this->user);

        $this->assertEquals('Test Gallery', $this->gallery->getTitle());
        $this->assertEquals('test-gallery', $this->gallery->getSlug());
        $this->assertEquals('A test gallery description', $this->gallery->getDescription());
        $this->assertEquals($this->user, $this->gallery->getAuthor());
    }

    public function testSlugGeneration(): void
    {
        $this->gallery->setTitle('Complex Gallery Title! With Special @#$% Characters');
        $this->assertEquals('complex-gallery-title-with-special-characters', $this->gallery->getSlug());
        
        // Test manual slug setting
        $this->gallery->setSlug('custom-gallery-slug');
        $this->assertEquals('custom-gallery-slug', $this->gallery->getSlug());
        
        // Test automatic generation on title change
        $this->gallery->setTitle('New Gallery Title');
        $this->assertEquals('new-gallery-title', $this->gallery->getSlug());
    }

    public function testVisibilityManagement(): void
    {
        // Test public visibility (default)
        $this->assertTrue($this->gallery->isPublic());
        $this->assertFalse($this->gallery->isPrivate());
        $this->assertFalse($this->gallery->requiresAccessCode());
        
        // Test private visibility
        $this->gallery->setVisibility('private');
        $this->assertFalse($this->gallery->isPublic());
        $this->assertTrue($this->gallery->isPrivate());
        $this->assertFalse($this->gallery->requiresAccessCode()); // No access code yet
        
        // Test private with access code
        $this->gallery->setAccessCode('secret123');
        $this->assertTrue($this->gallery->requiresAccessCode());
        $this->assertEquals('secret123', $this->gallery->getAccessCode());
        
        // Test back to public
        $this->gallery->setVisibility('public');
        $this->assertTrue($this->gallery->isPublic());
        $this->assertFalse($this->gallery->isPrivate());
    }

    public function testAccessCodeManagement(): void
    {
        $this->assertNull($this->gallery->getAccessCode());
        
        $this->gallery->setAccessCode('mySecretCode');
        $this->assertEquals('mySecretCode', $this->gallery->getAccessCode());
        
        // Access code only matters for private galleries
        $this->gallery->setVisibility('private');
        $this->assertTrue($this->gallery->requiresAccessCode());
        
        // Remove access code
        $this->gallery->setAccessCode(null);
        $this->assertFalse($this->gallery->requiresAccessCode());
        
        // Empty string should also be false
        $this->gallery->setAccessCode('');
        $this->assertFalse($this->gallery->requiresAccessCode());
    }

    public function testCoverImage(): void
    {
        $this->assertNull($this->gallery->getCoverImage());
        
        $this->gallery->setCoverImage('/path/to/cover.jpg');
        $this->assertEquals('/path/to/cover.jpg', $this->gallery->getCoverImage());
    }

    public function testImageManagement(): void
    {
        $image1 = $this->createMock(Image::class);
        $image2 = $this->createMock(Image::class);
        
        $this->assertEquals(0, $this->gallery->getImageCount());
        $this->assertNull($this->gallery->getFirstImage());
        
        // Test adding images
        $image1->expects($this->once())->method('setGallery')->with($this->gallery);
        $this->gallery->addImage($image1);
        
        $this->assertEquals(1, $this->gallery->getImageCount());
        $this->assertTrue($this->gallery->getImages()->contains($image1));
        
        // Test adding same image twice (should not duplicate)
        $this->gallery->addImage($image1); // Should not call setGallery again
        $this->assertEquals(1, $this->gallery->getImageCount());
        
        // Add second image
        $image2->expects($this->once())->method('setGallery')->with($this->gallery);
        $this->gallery->addImage($image2);
        $this->assertEquals(2, $this->gallery->getImageCount());
        
        // Test removing image
        $image1->expects($this->once())->method('getGallery')->willReturn($this->gallery);
        $image1->expects($this->once())->method('setGallery')->with(null);
        
        $this->gallery->removeImage($image1);
        $this->assertEquals(1, $this->gallery->getImageCount());
        $this->assertFalse($this->gallery->getImages()->contains($image1));
    }

    public function testRemoveImageWithDifferentGallery(): void
    {
        $otherGallery = new Gallery();
        $image = $this->createMock(Image::class);
        
        $this->gallery->addImage($image);
        
        // Mock image belonging to different gallery
        $image->expects($this->once())->method('getGallery')->willReturn($otherGallery);
        $image->expects($this->never())->method('setGallery')->with(null);
        
        $this->gallery->removeImage($image);
        $this->assertEquals(0, $this->gallery->getImageCount());
    }

    public function testGetCoverImageUrl(): void
    {
        // Test with no cover image and no images
        $this->assertNull($this->gallery->getCoverImageUrl());
        
        // Test with cover image set
        $this->gallery->setCoverImage('/uploads/covers/gallery_cover.jpg');
        $this->assertEquals('/uploads/covers/gallery_cover.jpg', $this->gallery->getCoverImageUrl());
        
        // Test fallback to first image
        $this->gallery->setCoverImage(null);
        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('getUrl')->willReturn('/uploads/images/first_image.jpg');
        
        $this->gallery->addImage($image);
        $this->assertEquals('/uploads/images/first_image.jpg', $this->gallery->getCoverImageUrl());
    }

    public function testGetFirstImage(): void
    {
        $this->assertNull($this->gallery->getFirstImage());
        
        $image1 = $this->createMock(Image::class);
        $image2 = $this->createMock(Image::class);
        
        $this->gallery->addImage($image1);
        $this->gallery->addImage($image2);
        
        $firstImage = $this->gallery->getFirstImage();
        $this->assertSame($image1, $firstImage);
    }

    public function testMetadata(): void
    {
        $this->assertNull($this->gallery->getMetadata());
        
        $metadata = ['photographer' => 'John Doe', 'location' => 'Paris'];
        $this->gallery->setMetadata($metadata);
        
        $this->assertEquals($metadata, $this->gallery->getMetadata());
    }

    public function testPreUpdateCallback(): void
    {
        $originalUpdatedAt = $this->gallery->getUpdatedAt();
        
        // Simulate PreUpdate event
        $this->gallery->onPreUpdate();
        
        $this->assertNotEquals($originalUpdatedAt, $this->gallery->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->gallery->getUpdatedAt());
    }

    public function testCompleteGalleryWorkflow(): void
    {
        // Create a complete gallery
        $this->gallery->setTitle('Wedding Photography')
                      ->setDescription('Beautiful wedding moments captured forever')
                      ->setVisibility('private')
                      ->setAccessCode('wedding2024')
                      ->setCoverImage('/uploads/covers/wedding_cover.jpg')
                      ->setAuthor($this->user);

        $metadata = ['event_date' => '2024-06-15', 'location' => 'Chateau de Versailles'];
        $this->gallery->setMetadata($metadata);

        // Test all properties
        $this->assertEquals('Wedding Photography', $this->gallery->getTitle());
        $this->assertEquals('wedding-photography', $this->gallery->getSlug());
        $this->assertTrue($this->gallery->isPrivate());
        $this->assertTrue($this->gallery->requiresAccessCode());
        $this->assertEquals('wedding2024', $this->gallery->getAccessCode());
        $this->assertEquals('/uploads/covers/wedding_cover.jpg', $this->gallery->getCoverImage());
        $this->assertEquals('/uploads/covers/wedding_cover.jpg', $this->gallery->getCoverImageUrl());
        $this->assertEquals($this->user, $this->gallery->getAuthor());
        $this->assertEquals($metadata, $this->gallery->getMetadata());
    }
}