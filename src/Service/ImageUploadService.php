<?php

namespace App\Service;

use App\Entity\Gallery;
use App\Entity\Image;
use App\Entity\User;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploadService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const THUMBNAIL_SIZE = 300;
    private const OPTIMIZE_MAX_WIDTH = 1920; // Largeur max pour les images uploadées

    public function __construct(
        private string $uploadDirectory,
        private string $thumbnailDirectory,
        private SluggerInterface $slugger,
        private EntityManagerInterface $entityManager,
        private ImageRepository $imageRepository,
        private ?ImageOptimizerService $imageOptimizer = null
    ) {
        // Ensure upload directories exist
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }
        if (!is_dir($this->thumbnailDirectory)) {
            mkdir($this->thumbnailDirectory, 0755, true);
        }
    }

    public function uploadImage(UploadedFile $file, Gallery $gallery, User $user): Image
    {
        $this->validateFile($file);

        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Generate unique filename
        $filename = $this->generateUniqueFilename($originalName);

        // Move file to upload directory
        $file->move($this->uploadDirectory, $filename);

        // Get image dimensions
        $fullPath = $this->uploadDirectory . '/' . $filename;
        $width = null;
        $height = null;

        // Optimiser l'image automatiquement (compression + WebP)
        if ($this->imageOptimizer) {
            try {
                $optimizeResult = $this->imageOptimizer->optimize(
                    $fullPath,
                    self::OPTIMIZE_MAX_WIDTH,
                    null
                );
                // Mettre à jour les dimensions après redimensionnement
                if (isset($optimizeResult['dimensions'])) {
                    $width = $optimizeResult['dimensions'][0];
                    $height = $optimizeResult['dimensions'][1];
                }
                error_log("Image optimized: saved {$optimizeResult['saved_bytes']} bytes");
            } catch (\Exception $e) {
                error_log('Image optimization failed: ' . $e->getMessage());
            }
        }

        // Get image dimensions if not already set by optimizer
        if ($width === null || $height === null) {
            try {
                if (function_exists('getimagesize')) {
                    $dimensions = getimagesize($fullPath);
                    if ($dimensions !== false) {
                        $width = $dimensions[0] ?? null;
                        $height = $dimensions[1] ?? null;
                    }
                }
            } catch (\Exception $e) {
                error_log('Failed to get image dimensions: ' . $e->getMessage());
            }
        }

        // Generate thumbnail (skip if GD not available)
        try {
            if (extension_loaded('gd')) {
                $this->generateThumbnail($fullPath, $filename);
            }
        } catch (\Exception $e) {
            error_log('Thumbnail generation failed: ' . $e->getMessage());
        }

        // Extract EXIF data if available (skip if not available)
        $exifData = null;
        try {
            $exifData = $this->extractExifData($fullPath);
        } catch (\Exception $e) {
            error_log('EXIF extraction failed: ' . $e->getMessage());
        }

        // Create Image entity
        $image = new Image();
        $image->setFilename($filename)
              ->setOriginalName($originalName)
              ->setMimeType($mimeType)
              ->setSize($size)
              ->setWidth($width)
              ->setHeight($height)
              ->setGallery($gallery)
              ->setUploadedBy($user)
              ->setPosition($this->imageRepository->getNextPosition($gallery))
              ->setExifData($exifData);

        // Auto-generate alt text from filename
        $altText = $this->generateAltText($originalName);
        $image->setAlt($altText);

        return $image;
    }

    public function uploadMultipleImages(array $files, Gallery $gallery, User $user): array
    {
        $images = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                try {
                    $image = $this->uploadImage($file, $gallery, $user);
                    $this->entityManager->persist($image);
                    $images[] = $image;
                } catch (\Exception $e) {
                    // Log error but continue with other files
                    error_log("Failed to upload {$file->getClientOriginalName()}: " . $e->getMessage());
                }
            }
        }

        if (!empty($images)) {
            $this->entityManager->flush();
        }

        return $images;
    }

    public function deleteImage(Image $image): void
    {
        $filename = $image->getFilename();
        
        // Delete original file
        $originalPath = $this->uploadDirectory . '/' . $filename;
        if (file_exists($originalPath)) {
            unlink($originalPath);
        }

        // Delete thumbnail
        $info = pathinfo($filename);
        $thumbnailName = $info['filename'] . '_thumb.' . $info['extension'];
        $thumbnailPath = $this->thumbnailDirectory . '/' . $thumbnailName;
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }

        $this->entityManager->remove($image);
        $this->entityManager->flush();
    }

    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(
                sprintf('File size (%s) exceeds maximum allowed size (%s)', 
                    $this->formatFileSize($file->getSize()),
                    $this->formatFileSize(self::MAX_FILE_SIZE)
                )
            );
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \InvalidArgumentException(
                sprintf('File type %s is not allowed. Allowed types: %s', 
                    $file->getMimeType(),
                    implode(', ', self::ALLOWED_MIME_TYPES)
                )
            );
        }
    }

    private function generateUniqueFilename(string $originalName): string
    {
        $info = pathinfo($originalName);
        $baseName = $this->slugger->slug($info['filename'])->lower();
        $extension = $info['extension'] ?? '';

        $filename = $baseName . '.' . $extension;
        $counter = 1;

        while (file_exists($this->uploadDirectory . '/' . $filename)) {
            $filename = $baseName . '-' . $counter . '.' . $extension;
            $counter++;
        }

        return $filename;
    }

    private function generateThumbnail(string $sourcePath, string $filename): void
    {
        $info = pathinfo($filename);
        $thumbnailName = $info['filename'] . '_thumb.' . $info['extension'];
        $thumbnailPath = $this->thumbnailDirectory . '/' . $thumbnailName;

        // Get original image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return;
        }

        [$width, $height, $type] = $imageInfo;

        // Calculate thumbnail dimensions maintaining aspect ratio
        if ($width > $height) {
            $thumbWidth = self::THUMBNAIL_SIZE;
            $thumbHeight = intval(($height * self::THUMBNAIL_SIZE) / $width);
        } else {
            $thumbHeight = self::THUMBNAIL_SIZE;
            $thumbWidth = intval(($width * self::THUMBNAIL_SIZE) / $height);
        }

        // Create image resource based on type
        $source = match($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
            default => null
        };

        if (!$source) {
            return;
        }

        // Create thumbnail
        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // Preserve transparency for PNG and GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);
        }

        // Resize image
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

        // Save thumbnail
        match($type) {
            IMAGETYPE_JPEG => imagejpeg($thumbnail, $thumbnailPath, 85),
            IMAGETYPE_PNG => imagepng($thumbnail, $thumbnailPath, 6),
            IMAGETYPE_GIF => imagegif($thumbnail, $thumbnailPath),
            IMAGETYPE_WEBP => imagewebp($thumbnail, $thumbnailPath, 85),
            default => null
        };

        // Clean up
        imagedestroy($source);
        imagedestroy($thumbnail);
    }

    private function extractExifData(string $imagePath): ?array
    {
        if (!function_exists('exif_read_data')) {
            return null;
        }

        try {
            $exif = exif_read_data($imagePath);
            if (!$exif) {
                return null;
            }

            // Extract relevant EXIF data
            return [
                'camera_make' => $exif['Make'] ?? null,
                'camera_model' => $exif['Model'] ?? null,
                'date_taken' => $exif['DateTime'] ?? null,
                'exposure_time' => $exif['ExposureTime'] ?? null,
                'f_number' => $exif['FNumber'] ?? null,
                'iso_speed' => $exif['ISOSpeedRatings'] ?? null,
                'focal_length' => $exif['FocalLength'] ?? null,
                'orientation' => $exif['Orientation'] ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateAltText(string $filename): string
    {
        $info = pathinfo($filename);
        $baseName = $info['filename'];
        
        // Convert filename to readable alt text
        $altText = str_replace(['-', '_'], ' ', $baseName);
        $altText = ucwords($altText);
        
        return $altText;
    }

    private function formatFileSize(int $size): string
    {
        if ($size >= 1048576) {
            return number_format($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) {
            return number_format($size / 1024, 2) . ' KB';
        } else {
            return $size . ' bytes';
        }
    }

    public function getTotalStorageUsed(): int
    {
        return $this->imageRepository->getTotalSize();
    }

    public function getFormattedStorageUsed(): string
    {
        return $this->formatFileSize($this->getTotalStorageUsed());
    }
}