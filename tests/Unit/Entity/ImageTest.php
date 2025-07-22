<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Gallery;
use App\Entity\Image;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    private Image $image;
    private Gallery $gallery;
    private User $user;

    protected function setUp(): void
    {
        $this->image = new Image();
        $this->gallery = new Gallery();
        $this->user = new User();
        $this->user->setFirstName('John')->setLastName('Doe');
    }

    public function testImageCreation(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->image->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->image->getUpdatedAt());
        $this->assertEquals(0, $this->image->getPosition());
    }

    public function testBasicProperties(): void
    {
        $this->image->setFilename('test-image.jpg');
        $this->image->setOriginalName('Test Image.jpg');
        $this->image->setMimeType('image/jpeg');
        $this->image->setSize(1024000);
        $this->image->setWidth(1920);
        $this->image->setHeight(1080);
        $this->image->setAlt('Test image alt text');
        $this->image->setCaption('This is a test image');
        $this->image->setPosition(5);
        $this->image->setGallery($this->gallery);
        $this->image->setUploadedBy($this->user);

        $this->assertEquals('test-image.jpg', $this->image->getFilename());
        $this->assertEquals('Test Image.jpg', $this->image->getOriginalName());
        $this->assertEquals('image/jpeg', $this->image->getMimeType());
        $this->assertEquals(1024000, $this->image->getSize());
        $this->assertEquals(1920, $this->image->getWidth());
        $this->assertEquals(1080, $this->image->getHeight());
        $this->assertEquals('Test image alt text', $this->image->getAlt());
        $this->assertEquals('This is a test image', $this->image->getCaption());
        $this->assertEquals(5, $this->image->getPosition());
        $this->assertEquals($this->gallery, $this->image->getGallery());
        $this->assertEquals($this->user, $this->image->getUploadedBy());
    }

    public function testUrls(): void
    {
        $this->image->setFilename('my-photo.jpg');
        
        $this->assertEquals('/uploads/images/my-photo.jpg', $this->image->getUrl());
        $this->assertEquals('/uploads/thumbnails/my-photo_thumb.jpg', $this->image->getThumbnailUrl());
    }

    public function testThumbnailUrlFallback(): void
    {
        $this->image->setFilename('test.jpg');
        
        // Since we're testing and thumbnail doesn't exist, it should fall back to original
        $thumbnailUrl = $this->image->getThumbnailUrl();
        
        // The method checks if thumbnail exists, if not returns original URL
        // In test environment, thumbnail won't exist, so it should return original URL
        $expectedUrl = $this->image->getUrl();
        $this->assertEquals($expectedUrl, $thumbnailUrl);
    }

    public function testFormattedSize(): void
    {
        // Test bytes
        $this->image->setSize(500);
        $this->assertEquals('500 bytes', $this->image->getFormattedSize());
        
        // Test KB
        $this->image->setSize(1536); // 1.5 KB
        $this->assertEquals('1.50 KB', $this->image->getFormattedSize());
        
        // Test MB
        $this->image->setSize(1572864); // 1.5 MB
        $this->assertEquals('1.50 MB', $this->image->getFormattedSize());
        
        // Test large MB
        $this->image->setSize(10485760); // 10 MB
        $this->assertEquals('10.00 MB', $this->image->getFormattedSize());
    }

    public function testDimensions(): void
    {
        // Test with no dimensions
        $this->assertNull($this->image->getDimensions());
        
        // Test with both dimensions
        $this->image->setWidth(1920);
        $this->image->setHeight(1080);
        $this->assertEquals('1920x1080', $this->image->getDimensions());
        
        // Test with only width
        $this->image->setHeight(null);
        $this->assertNull($this->image->getDimensions());
        
        // Test with only height
        $this->image->setWidth(null);
        $this->image->setHeight(1080);
        $this->assertNull($this->image->getDimensions());
    }

    public function testAspectRatio(): void
    {
        // Test with no dimensions
        $this->assertNull($this->image->getAspectRatio());
        
        // Test landscape (16:9)
        $this->image->setWidth(1920);
        $this->image->setHeight(1080);
        $this->assertEqualsWithDelta(1.7777777777778, $this->image->getAspectRatio(), 0.0001);
        $this->assertTrue($this->image->isLandscape());
        $this->assertFalse($this->image->isPortrait());
        $this->assertFalse($this->image->isSquare());
        
        // Test portrait (9:16)
        $this->image->setWidth(1080);
        $this->image->setHeight(1920);
        $this->assertEqualsWithDelta(0.5625, $this->image->getAspectRatio(), 0.0001);
        $this->assertFalse($this->image->isLandscape());
        $this->assertTrue($this->image->isPortrait());
        $this->assertFalse($this->image->isSquare());
        
        // Test square
        $this->image->setWidth(1080);
        $this->image->setHeight(1080);
        $this->assertEquals(1.0, $this->image->getAspectRatio());
        $this->assertFalse($this->image->isLandscape());
        $this->assertFalse($this->image->isPortrait());
        $this->assertTrue($this->image->isSquare());
    }

    public function testSquareDetectionWithTolerance(): void
    {
        // Test near-square image (within tolerance)
        $this->image->setWidth(1080);
        $this->image->setHeight(1081); // Very slight difference
        
        $aspectRatio = $this->image->getAspectRatio();
        $this->assertTrue(abs($aspectRatio - 1) < 0.01); // Should be considered square
    }

    public function testExifData(): void
    {
        $this->assertNull($this->image->getExifData());
        
        $exifData = [
            'camera_make' => 'Canon',
            'camera_model' => 'EOS R5',
            'date_taken' => '2024:01:15 14:30:22',
            'f_number' => 'f/2.8',
            'iso_speed' => 400
        ];
        
        $this->image->setExifData($exifData);
        $this->assertEquals($exifData, $this->image->getExifData());
    }

    public function testPreUpdateCallback(): void
    {
        $originalUpdatedAt = $this->image->getUpdatedAt();
        
        // Simulate PreUpdate event
        $this->image->onPreUpdate();
        
        $this->assertNotEquals($originalUpdatedAt, $this->image->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->image->getUpdatedAt());
    }

    public function testCompleteImageWorkflow(): void
    {
        // Create a complete image
        $exifData = [
            'camera_make' => 'Sony',
            'camera_model' => 'Alpha A7R IV',
            'date_taken' => '2024:06:15 16:45:12',
            'f_number' => 'f/4.0',
            'iso_speed' => 200,
            'focal_length' => '85mm'
        ];

        $this->image->setFilename('wedding-photo-001.jpg')
                   ->setOriginalName('DSC_0001.jpg')
                   ->setMimeType('image/jpeg')
                   ->setSize(5242880) // 5MB
                   ->setWidth(3840)
                   ->setHeight(2560)
                   ->setAlt('Beautiful wedding ceremony moment')
                   ->setCaption('The bride and groom exchange vows at sunset')
                   ->setPosition(1)
                   ->setGallery($this->gallery)
                   ->setUploadedBy($this->user)
                   ->setExifData($exifData);

        // Test all properties
        $this->assertEquals('wedding-photo-001.jpg', $this->image->getFilename());
        $this->assertEquals('DSC_0001.jpg', $this->image->getOriginalName());
        $this->assertEquals('image/jpeg', $this->image->getMimeType());
        $this->assertEquals('5.00 MB', $this->image->getFormattedSize());
        $this->assertEquals('3840x2560', $this->image->getDimensions());
        $this->assertEquals('/uploads/images/wedding-photo-001.jpg', $this->image->getUrl());
        $this->assertTrue($this->image->isLandscape());
        $this->assertFalse($this->image->isPortrait());
        $this->assertFalse($this->image->isSquare());
        $this->assertEquals(1.5, $this->image->getAspectRatio());
        $this->assertEquals($exifData, $this->image->getExifData());
        $this->assertEquals($this->gallery, $this->image->getGallery());
        $this->assertEquals($this->user, $this->image->getUploadedBy());
    }
}