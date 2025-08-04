<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Simplified image service that works without GD extension
 * Provides basic file operations and metadata handling
 */
class SimpleImageService
{
    public function __construct(
        private LoggerInterface $logger,
        private ParameterBagInterface $params
    ) {}

    /**
     * Get image information and metadata
     */
    public function getImageInfo(string $imagePath): array
    {
        $fullPath = $this->getFullPath($imagePath);
        
        if (!file_exists($fullPath)) {
            throw new \InvalidArgumentException("Image file not found: {$fullPath}");
        }

        $info = [
            'path' => $imagePath,
            'full_path' => $fullPath,
            'size' => filesize($fullPath),
            'size_formatted' => $this->formatFileSize(filesize($fullPath)),
            'modified' => filemtime($fullPath),
            'extension' => pathinfo($fullPath, PATHINFO_EXTENSION),
            'basename' => pathinfo($fullPath, PATHINFO_BASENAME),
            'filename' => pathinfo($fullPath, PATHINFO_FILENAME),
            'is_image' => $this->isImageFile($fullPath)
        ];

        // Try to get image dimensions if possible
        if (function_exists('getimagesize') && $info['is_image']) {
            $imageInfo = @getimagesize($fullPath);
            if ($imageInfo !== false) {
                $info['width'] = $imageInfo[0];
                $info['height'] = $imageInfo[1];
                $info['mime_type'] = $imageInfo['mime'];
                $info['dimensions'] = "{$imageInfo[0]}x{$imageInfo[1]}";
            }
        }

        return $info;
    }

    /**
     * Copy image with watermark metadata
     */
    public function markImageAsWatermarked(string $imagePath, array $watermarkInfo = []): string
    {
        $fullPath = $this->getFullPath($imagePath);
        $pathInfo = pathinfo($fullPath);
        
        $watermarkedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_watermarked.' . $pathInfo['extension'];
        
        // Copy the original file
        if (!copy($fullPath, $watermarkedPath)) {
            throw new \RuntimeException("Failed to copy image for watermarking: {$imagePath}");
        }

        // Create metadata file
        $metadataPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_watermarked.meta';
        $metadata = array_merge([
            'original_file' => $imagePath,
            'watermark_applied' => true,
            'watermark_timestamp' => date('Y-m-d H:i:s'),
            'watermark_text' => '© ZeTilt CMS',
            'processing_method' => 'metadata_only'
        ], $watermarkInfo);

        file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));

        $this->logger->info('Image marked as watermarked (metadata only)', [
            'original_path' => $imagePath,
            'watermarked_path' => $this->getRelativePath($watermarkedPath),
            'metadata' => $metadata
        ]);

        return $this->getRelativePath($watermarkedPath);
    }

    /**
     * Create compressed version by copying and adding metadata
     */
    public function createCompressedVersion(string $imagePath, array $options = []): string
    {
        $fullPath = $this->getFullPath($imagePath);
        $pathInfo = pathinfo($fullPath);
        
        $compressedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_compressed.' . $pathInfo['extension'];
        
        // Copy the original file
        if (!copy($fullPath, $compressedPath)) {
            throw new \RuntimeException("Failed to copy image for compression: {$imagePath}");
        }

        // Create metadata file
        $metadataPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_compressed.meta';
        $metadata = array_merge([
            'original_file' => $imagePath,
            'compressed' => true,
            'compression_timestamp' => date('Y-m-d H:i:s'),
            'target_quality' => $options['quality'] ?? 80,
            'target_max_width' => $options['max_width'] ?? null,
            'target_max_height' => $options['max_height'] ?? null,
            'processing_method' => 'metadata_only',
            'note' => 'GD extension not available - file copied with compression metadata'
        ], $options);

        file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));

        $this->logger->info('Image marked as compressed (metadata only)', [
            'original_path' => $imagePath,
            'compressed_path' => $this->getRelativePath($compressedPath),
            'metadata' => $metadata
        ]);

        return $this->getRelativePath($compressedPath);
    }

    /**
     * Generate thumbnail placeholders
     */
    public function generateThumbnailPlaceholders(string $imagePath, array $sizes = []): array
    {
        $defaultSizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200]
        ];

        $sizes = array_merge($defaultSizes, $sizes);
        $thumbnails = [];
        $pathInfo = pathinfo($this->getFullPath($imagePath));
        
        $thumbnailDir = $pathInfo['dirname'] . '/thumbnails';
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        foreach ($sizes as $sizeName => $dimensions) {
            $thumbnailPath = "{$thumbnailDir}/{$pathInfo['filename']}_{$sizeName}.{$pathInfo['extension']}";
            
            // Copy original as placeholder
            copy($this->getFullPath($imagePath), $thumbnailPath);
            
            // Create metadata
            $metadataPath = "{$thumbnailDir}/{$pathInfo['filename']}_{$sizeName}.meta";
            $metadata = [
                'original_file' => $imagePath,
                'size_name' => $sizeName,
                'target_width' => $dimensions['width'],
                'target_height' => $dimensions['height'],
                'created' => date('Y-m-d H:i:s'),
                'processing_method' => 'placeholder_copy',
                'note' => 'GD extension not available - original file copied as placeholder'
            ];
            
            file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
            $thumbnails[$sizeName] = $this->getRelativePath($thumbnailPath);
        }

        $this->logger->info('Thumbnail placeholders generated', [
            'original_path' => $imagePath,
            'sizes_generated' => array_keys($thumbnails),
            'thumbnail_dir' => $thumbnailDir
        ]);

        return $thumbnails;
    }

    /**
     * Complete image processing (metadata-based fallback)
     */
    public function processImageComplete(string $imagePath, array $options = []): array
    {
        $options = array_merge([
            'add_watermark' => true,
            'compress' => true,
            'generate_thumbnails' => true,
            'watermark_text' => '© ZeTilt CMS',
            'quality' => 80
        ], $options);

        $results = [
            'original' => $imagePath,
            'processed' => null,
            'watermarked' => null,
            'thumbnails' => [],
            'method' => 'metadata_fallback',
            'note' => 'GD extension not available - using metadata-based processing'
        ];

        try {
            // Step 1: Create compressed version
            if ($options['compress']) {
                $results['processed'] = $this->createCompressedVersion($imagePath, [
                    'quality' => $options['quality'],
                    'max_width' => $options['max_width'] ?? null,
                    'max_height' => $options['max_height'] ?? null
                ]);
            } else {
                $results['processed'] = $imagePath;
            }

            // Step 2: Add watermark metadata
            if ($options['add_watermark']) {
                $results['watermarked'] = $this->markImageAsWatermarked(
                    $results['processed'], 
                    ['watermark_text' => $options['watermark_text']]
                );
            }

            // Step 3: Generate thumbnail placeholders
            if ($options['generate_thumbnails']) {
                $sourceForThumbnails = $results['watermarked'] ?: $results['processed'];
                $results['thumbnails'] = $this->generateThumbnailPlaceholders($sourceForThumbnails);
            }

            $this->logger->info('Complete image processing finished (metadata fallback)', [
                'original_path' => $imagePath,
                'steps_completed' => array_keys(array_filter($results))
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Image processing failed', [
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Check if file is an image
     */
    private function isImageFile(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];
        
        if (!in_array($extension, $imageExtensions)) {
            return false;
        }

        // Try to check MIME type if function is available
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filePath);
            return str_starts_with($mimeType, 'image/');
        }

        return true; // Assume it's an image based on extension
    }

    /**
     * Get system requirements for full image processing
     */
    public function getSystemRequirements(): array
    {
        return [
            'gd_extension' => [
                'required' => true,
                'available' => extension_loaded('gd'),
                'description' => 'GD extension for image manipulation'
            ],
            'getimagesize_function' => [
                'required' => false,
                'available' => function_exists('getimagesize'),
                'description' => 'Function to get image dimensions'
            ],
            'mime_content_type_function' => [
                'required' => false,
                'available' => function_exists('mime_content_type'),
                'description' => 'Function to detect MIME types'
            ]
        ];
    }

    /**
     * Check if full image processing is available
     */
    public function isFullProcessingAvailable(): bool
    {
        return extension_loaded('gd');
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get full filesystem path
     */
    private function getFullPath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }
        
        $projectDir = $this->params->get('kernel.project_dir');
        return $projectDir . '/' . ltrim($path, '/');
    }

    /**
     * Get relative path from full path
     */
    private function getRelativePath(string $fullPath): string
    {
        $projectDir = $this->params->get('kernel.project_dir');
        return str_replace($projectDir . '/', '', $fullPath);
    }
}