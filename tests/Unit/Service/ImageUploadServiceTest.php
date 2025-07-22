<?php

namespace App\Tests\Unit\Service;

use App\Entity\Gallery;
use App\Entity\Image;
use App\Entity\User;
use App\Repository\ImageRepository;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploadServiceTest extends TestCase
{
    private ImageUploadService $service;
    private string $uploadDirectory;
    private string $thumbnailDirectory;
    private SluggerInterface $slugger;
    private EntityManagerInterface $entityManager;
    private ImageRepository $imageRepository;

    protected function setUp(): void
    {
        $this->uploadDirectory = sys_get_temp_dir() . '/test_uploads';
        $this->thumbnailDirectory = sys_get_temp_dir() . '/test_thumbnails';
        $this->slugger = new class implements SluggerInterface {
            public function slug(string $string, string $separator = '-', ?string $locale = null): \Symfony\Component\String\AbstractUnicodeString {
                return new \Symfony\Component\String\UnicodeString(strtolower(preg_replace('/[^a-zA-Z0-9]/', $separator, $string)));
            }
        };
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->imageRepository = $this->createMock(ImageRepository::class);

        // Create test directories
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }
        if (!is_dir($this->thumbnailDirectory)) {
            mkdir($this->thumbnailDirectory, 0755, true);
        }

        $this->service = new ImageUploadService(
            $this->uploadDirectory,
            $this->thumbnailDirectory,
            $this->slugger,
            $this->entityManager,
            $this->imageRepository
        );
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $this->removeDirectory($this->uploadDirectory);
        $this->removeDirectory($this->thumbnailDirectory);
    }

    public function testValidateFileSuccess(): void
    {
        // Create a valid test file
        $testFile = $this->createTestImage();
        $uploadedFile = new UploadedFile(
            $testFile,
            'test.jpg',
            'image/jpeg',
            null,
            true // test mode
        );

        $gallery = new Gallery();
        $user = new User();

        $this->imageRepository->method('getNextPosition')->willReturn(1);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->uploadImage($uploadedFile, $gallery, $user);

        $this->assertInstanceOf(Image::class, $result);
        $this->assertEquals('test.jpg', $result->getOriginalName());
        $this->assertEquals('image/jpeg', $result->getMimeType());
        $this->assertEquals($gallery, $result->getGallery());
        $this->assertEquals($user, $result->getUploadedBy());
        $this->assertEquals(1, $result->getPosition());
    }

    public function testValidateFileInvalidMimeType(): void
    {
        $testFile = $this->createTestFile('test.txt', 'text content');
        $uploadedFile = new UploadedFile(
            $testFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $gallery = new Gallery();
        $user = new User();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File type text/plain is not allowed');

        $this->service->uploadImage($uploadedFile, $gallery, $user);
    }

    public function testValidateFileTooLarge(): void
    {
        // Create a file larger than the limit (10MB)
        $testFile = $this->createTestFile('large.jpg', str_repeat('x', 11 * 1024 * 1024));
        $uploadedFile = new UploadedFile(
            $testFile,
            'large.jpg',
            'image/jpeg',
            null,
            true
        );

        $gallery = new Gallery();
        $user = new User();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File size');

        $this->service->uploadImage($uploadedFile, $gallery, $user);
    }

    public function testUploadMultipleImages(): void
    {
        $testFile1 = $this->createTestImage('image1.jpg');
        $testFile2 = $this->createTestImage('image2.jpg');

        $uploadedFiles = [
            new UploadedFile($testFile1, 'image1.jpg', 'image/jpeg', null, true),
            new UploadedFile($testFile2, 'image2.jpg', 'image/jpeg', null, true),
        ];

        $gallery = new Gallery();
        $user = new User();

        $this->imageRepository->method('getNextPosition')
            ->willReturnOnConsecutiveCalls(1, 2);

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $results = $this->service->uploadMultipleImages($uploadedFiles, $gallery, $user);

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(Image::class, $results);
    }

    public function testUploadMultipleImagesWithErrors(): void
    {
        $validFile = $this->createTestImage('valid.jpg');
        $invalidFile = $this->createTestFile('invalid.txt', 'text');

        $uploadedFiles = [
            new UploadedFile($validFile, 'valid.jpg', 'image/jpeg', null, true),
            new UploadedFile($invalidFile, 'invalid.txt', 'text/plain', null, true),
        ];

        $gallery = new Gallery();
        $user = new User();

        $this->imageRepository->method('getNextPosition')->willReturn(1);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $results = $this->service->uploadMultipleImages($uploadedFiles, $gallery, $user);

        // Should return only the valid image
        $this->assertCount(1, $results);
        $this->assertEquals('valid.jpg', $results[0]->getOriginalName());
    }

    public function testDeleteImage(): void
    {
        // Create a test image file
        $testImagePath = $this->uploadDirectory . '/test_delete.jpg';
        file_put_contents($testImagePath, 'fake image content');

        $testThumbnailPath = $this->thumbnailDirectory . '/test_delete_thumb.jpg';
        file_put_contents($testThumbnailPath, 'fake thumbnail content');

        $image = new Image();
        $image->setFilename('test_delete.jpg');

        $this->entityManager->expects($this->once())->method('remove')->with($image);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->deleteImage($image);

        $this->assertFalse(file_exists($testImagePath));
        $this->assertFalse(file_exists($testThumbnailPath));
    }

    public function testGetTotalStorageUsed(): void
    {
        $this->imageRepository->method('getTotalSize')->willReturn(5000000);

        $result = $this->service->getTotalStorageUsed();
        $this->assertEquals(5000000, $result);
    }

    public function testGetFormattedStorageUsed(): void
    {
        $this->imageRepository->method('getTotalSize')->willReturn(5242880); // 5MB

        $result = $this->service->getFormattedStorageUsed();
        $this->assertEquals('5.00 MB', $result);
    }

    private function createTestImage(string $filename = 'test.jpg'): string
    {
        $path = sys_get_temp_dir() . '/' . $filename;
        
        // Create a minimal valid JPEG
        $imageData = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        file_put_contents($path, $imageData);
        
        return $path;
    }

    private function createTestFile(string $filename, string $content): string
    {
        $path = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($path, $content);
        return $path;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($directory);
    }
}