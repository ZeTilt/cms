<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageProcessingService
{
    private bool $gdAvailable;

    public function __construct(
        private LoggerInterface $logger,
        private ParameterBagInterface $params,
        private string $uploadsPath = 'public/uploads',
        private string $watermarkPath = 'public/assets/watermark.png'
    ) {
        $this->gdAvailable = extension_loaded('gd');
        
        if (!$this->gdAvailable) {
            $this->logger->warning('GD extension not available. Image processing features will be limited.');
        }
    }

    /**
     * Check if GD extension is available
     */
    public function isGdAvailable(): bool
    {
        return $this->gdAvailable;
    }

    /**
     * Add watermark to an image
     */
    public function addWatermark(string $imagePath, ?string $watermarkText = null, ?string $outputPath = null, array $options = []): ?string
    {
        if (!$this->gdAvailable) {
            $this->logger->warning('Cannot add watermark: GD extension not available');
            return null;
        }

        $fullImagePath = $this->getFullPath($imagePath);
        
        if (!file_exists($fullImagePath)) {
            throw new \InvalidArgumentException("Image file not found: {$fullImagePath}");
        }

        // Create image resource from file
        $imageInfo = getimagesize($fullImagePath);
        if ($imageInfo === false) {
            throw new \InvalidArgumentException("Invalid image file: {$fullImagePath}");
        }

        $image = $this->createImageResource($fullImagePath, $imageInfo['mime']);
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        // Merge default options
        $options = array_merge([
            'position' => 'bottom-right', // top-left, top-right, bottom-left, bottom-right, center
            'opacity' => 70, // 0-100
            'margin' => 20,
            'font_size' => 14,
            'text_color' => '#FFFFFF',
            'background_color' => '#000000',
            'background_opacity' => 50,
            'use_image_watermark' => false,
            'watermark_image_path' => null,
            'watermark_scale' => 100
        ], $options);

        if ($options['use_image_watermark'] && !empty($options['watermark_image_path']) && file_exists($options['watermark_image_path'])) {
            $this->addImageWatermark($image, $imageWidth, $imageHeight, $options);
        } elseif ($options['use_image_watermark'] && file_exists($this->watermarkPath)) {
            // Fallback to default watermark path
            $options['watermark_image_path'] = $this->watermarkPath;
            $this->addImageWatermark($image, $imageWidth, $imageHeight, $options);
        } else {
            $this->addTextWatermark($image, $imageWidth, $imageHeight, $watermarkText ?: '© ZeTilt CMS', $options);
        }

        // Determine output path
        if (!$outputPath) {
            $pathInfo = pathinfo($fullImagePath);
            $outputPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_watermarked.' . $pathInfo['extension'];
        } else {
            $outputPath = $this->getFullPath($outputPath);
        }

        // Save watermarked image
        $this->saveImage($image, $outputPath, $imageInfo['mime'], $options['quality'] ?? 85);
        imagedestroy($image);

        $this->logger->info('Watermark added to image', [
            'original_path' => $imagePath,
            'output_path' => $outputPath,
            'watermark_type' => $options['use_image_watermark'] ? 'image' : 'text'
        ]);

        return $this->getRelativePath($outputPath);
    }

    /**
     * Compress image with specified quality and dimensions
     */
    public function compressImage(string $imagePath, ?string $outputPath = null, array $options = []): string
    {
        $fullImagePath = $this->getFullPath($imagePath);
        
        if (!file_exists($fullImagePath)) {
            throw new \InvalidArgumentException("Image file not found: {$fullImagePath}");
        }

        $imageInfo = getimagesize($fullImagePath);
        if ($imageInfo === false) {
            throw new \InvalidArgumentException("Invalid image file: {$fullImagePath}");
        }

        $image = $this->createImageResource($fullImagePath, $imageInfo['mime']);
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Merge default options
        $options = array_merge([
            'quality' => 80, // 1-100 for JPEG
            'max_width' => null,
            'max_height' => null,
            'maintain_aspect_ratio' => true,
            'strip_metadata' => true
        ], $options);

        // Calculate new dimensions if resizing is needed
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;

        if ($options['max_width'] || $options['max_height']) {
            [$newWidth, $newHeight] = $this->calculateNewDimensions(
                $originalWidth, 
                $originalHeight, 
                $options['max_width'], 
                $options['max_height'], 
                $options['maintain_aspect_ratio']
            );
        }

        // Create new image if resizing
        if ($newWidth !== $originalWidth || $newHeight !== $originalHeight) {
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG/GIF
            if ($imageInfo['mime'] === 'image/png' || $imageInfo['mime'] === 'image/gif') {
                $this->preserveTransparency($resizedImage, $image, $imageInfo['mime']);
            }
            
            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            imagedestroy($image);
            $image = $resizedImage;
        }

        // Determine output path
        if (!$outputPath) {
            $pathInfo = pathinfo($fullImagePath);
            $outputPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_compressed.' . $pathInfo['extension'];
        } else {
            $outputPath = $this->getFullPath($outputPath);
        }

        // Save compressed image
        $this->saveImage($image, $outputPath, $imageInfo['mime'], $options['quality']);
        imagedestroy($image);

        // Calculate compression ratio
        $originalSize = filesize($fullImagePath);
        $compressedSize = filesize($outputPath);
        $compressionRatio = round((1 - $compressedSize / $originalSize) * 100, 2);

        $this->logger->info('Image compressed', [
            'original_path' => $imagePath,
            'output_path' => $outputPath,
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'compression_ratio' => "{$compressionRatio}%",
            'new_dimensions' => "{$newWidth}x{$newHeight}"
        ]);

        return $this->getRelativePath($outputPath);
    }

    /**
     * Generate a single thumbnail and return file content
     */
    public function generateThumbnail(string $imagePath, int $width = 300, int $height = 200): ?string
    {
        if (!$this->gdAvailable) {
            $this->logger->warning('Cannot generate thumbnail: GD extension not available');
            return null;
        }

        // Handle both absolute and relative paths
        $fullImagePath = str_starts_with($imagePath, '/') ? $imagePath : $this->getFullPath($imagePath);
        
        if (!file_exists($fullImagePath)) {
            $this->logger->error('Thumbnail source image not found', ['path' => $fullImagePath, 'original' => $imagePath]);
            return null;
        }

        try {
            $this->logger->info('Starting thumbnail generation', ['path' => $fullImagePath]);
            
            $imageInfo = getimagesize($fullImagePath);
            if ($imageInfo === false) {
                $this->logger->error('Invalid image file for thumbnail', ['path' => $fullImagePath]);
                return null;
            }

            $image = $this->createImageResource($fullImagePath, $imageInfo['mime']);
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            // Calculate new dimensions maintaining aspect ratio
            [$newWidth, $newHeight] = $this->calculateNewDimensions(
                $originalWidth, 
                $originalHeight, 
                $width, 
                $height, 
                true
            );

            // Create thumbnail
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG/GIF
            if ($imageInfo['mime'] === 'image/png' || $imageInfo['mime'] === 'image/gif') {
                $this->preserveTransparency($thumbnail, $image, $imageInfo['mime']);
            }
            
            imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            
            // Capture image content
            ob_start();
            imagejpeg($thumbnail, null, 85);
            $thumbnailContent = ob_get_contents();
            ob_end_clean();
            
            imagedestroy($image);
            imagedestroy($thumbnail);
            
            return $thumbnailContent;
            
        } catch (\Exception $e) {
            $this->logger->error('Thumbnail generation failed', [
                'image_path' => $imagePath,
                'full_path' => $fullImagePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Generate multiple sizes (thumbnails) of an image
     */
    public function generateThumbnails(string $imagePath, array $sizes = [], ?string $outputDir = null): array
    {
        $defaultSizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200]
        ];

        $sizes = array_merge($defaultSizes, $sizes);
        $thumbnails = [];

        $pathInfo = pathinfo($imagePath);
        $baseName = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        if (!$outputDir) {
            $outputDir = dirname($this->getFullPath($imagePath)) . '/thumbnails';
        } else {
            $outputDir = $this->getFullPath($outputDir);
        }

        // Create output directory if it doesn't exist
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        foreach ($sizes as $sizeName => $dimensions) {
            $thumbnailPath = "{$outputDir}/{$baseName}_{$sizeName}.{$extension}";
            
            $compressedPath = $this->compressImage($imagePath, $thumbnailPath, [
                'max_width' => $dimensions['width'],
                'max_height' => $dimensions['height'],
                'quality' => 85,
                'maintain_aspect_ratio' => true
            ]);

            $thumbnails[$sizeName] = $this->getRelativePath($compressedPath);
        }

        $this->logger->info('Thumbnails generated', [
            'original_path' => $imagePath,
            'sizes_generated' => array_keys($thumbnails),
            'output_dir' => $outputDir
        ]);

        return $thumbnails;
    }

    /**
     * Create optimized image with watermark and compression
     */
    public function processImageComplete(string $imagePath, array $options = []): array
    {
        $options = array_merge([
            'add_watermark' => true,
            'compress' => true,
            'generate_thumbnails' => true,
            'watermark_text' => null,
            'quality' => 80,
            'max_width' => 1920,
            'max_height' => 1080
        ], $options);

        $results = [
            'original' => $imagePath,
            'processed' => null,
            'watermarked' => null,
            'thumbnails' => []
        ];

        try {
            // Step 1: Compress original if needed
            if ($options['compress']) {
                $results['processed'] = $this->compressImage($imagePath, null, [
                    'quality' => $options['quality'],
                    'max_width' => $options['max_width'],
                    'max_height' => $options['max_height']
                ]);
            } else {
                $results['processed'] = $imagePath;
            }

            // Step 2: Add watermark
            if ($options['add_watermark']) {
                $results['watermarked'] = $this->addWatermark(
                    $results['processed'], 
                    $options['watermark_text']
                );
            }

            // Step 3: Generate thumbnails
            if ($options['generate_thumbnails']) {
                $sourceForThumbnails = $results['watermarked'] ?: $results['processed'];
                $results['thumbnails'] = $this->generateThumbnails($sourceForThumbnails);
            }

            $this->logger->info('Complete image processing finished', [
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
     * Add text watermark to image
     */
    private function addTextWatermark($image, int $imageWidth, int $imageHeight, string $text, array $options): void
    {
        $fontSize = $options['font_size'];
        $margin = $options['margin'];
        
        // Create text box
        $textBounds = imagettfbbox($fontSize, 0, $this->getDefaultFont(), $text);
        $textWidth = abs($textBounds[4] - $textBounds[0]);
        $textHeight = abs($textBounds[5] - $textBounds[1]);

        // Calculate position
        [$x, $y] = $this->calculateWatermarkPosition($imageWidth, $imageHeight, $textWidth, $textHeight, $options['position'], $margin);

        // Create background if needed
        if ($options['background_opacity'] > 0) {
            $bgColor = $this->hexToRgb($options['background_color']);
            $bgColorAlpha = imagecolorallocatealpha($image, $bgColor[0], $bgColor[1], $bgColor[2], 127 - ($options['background_opacity'] * 1.27));
            imagefilledrectangle($image, $x - 5, $y - $textHeight - 5, $x + $textWidth + 5, $y + 5, $bgColorAlpha);
        }

        // Add text
        $textColor = $this->hexToRgb($options['text_color']);
        $textColorAlpha = imagecolorallocatealpha($image, $textColor[0], $textColor[1], $textColor[2], 127 - ($options['opacity'] * 1.27));
        
        imagettftext($image, $fontSize, 0, $x, $y, $textColorAlpha, $this->getDefaultFont(), $text);
    }

    /**
     * Add image watermark
     */
    private function addImageWatermark($image, int $imageWidth, int $imageHeight, array $options): void
    {
        $watermarkPath = $options['watermark_image_path'] ?? $this->watermarkPath;
        
        // Create watermark resource based on file type
        $imageInfo = getimagesize($watermarkPath);
        if ($imageInfo === false) {
            return;
        }
        
        $watermark = $this->createImageResource($watermarkPath, $imageInfo['mime']);
        $originalWatermarkWidth = imagesx($watermark);
        $originalWatermarkHeight = imagesy($watermark);

        // Apply scaling
        $scale = ($options['watermark_scale'] ?? 100) / 100;
        $watermarkWidth = (int)($originalWatermarkWidth * $scale);
        $watermarkHeight = (int)($originalWatermarkHeight * $scale);
        
        // Create scaled watermark if needed
        if ($scale !== 1.0) {
            $scaledWatermark = imagecreatetruecolor($watermarkWidth, $watermarkHeight);
            
            // Preserve transparency
            imagealphablending($scaledWatermark, false);
            imagesavealpha($scaledWatermark, true);
            $transparent = imagecolorallocatealpha($scaledWatermark, 255, 255, 255, 127);
            imagefill($scaledWatermark, 0, 0, $transparent);
            
            imagecopyresampled($scaledWatermark, $watermark, 0, 0, 0, 0, 
                              $watermarkWidth, $watermarkHeight, 
                              $originalWatermarkWidth, $originalWatermarkHeight);
            
            imagedestroy($watermark);
            $watermark = $scaledWatermark;
        }

        // Calculate position
        [$x, $y] = $this->calculateWatermarkPosition($imageWidth, $imageHeight, $watermarkWidth, $watermarkHeight, $options['position'], $options['margin']);

        // Apply opacity
        if ($options['opacity'] < 100) {
            $this->applyOpacityToImage($watermark, $options['opacity']);
        }

        // Copy watermark with alpha blending
        imagealphablending($image, true);
        imagecopy($image, $watermark, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight);
        imagedestroy($watermark);
    }

    /**
     * Calculate watermark position based on placement option
     */
    private function calculateWatermarkPosition(int $imageWidth, int $imageHeight, int $watermarkWidth, int $watermarkHeight, string $position, int $margin): array
    {
        return match ($position) {
            'top-left' => [$margin, $margin],
            'top-right' => [$imageWidth - $watermarkWidth - $margin, $margin],
            'bottom-left' => [$margin, $imageHeight - $watermarkHeight - $margin],
            'bottom-right' => [$imageWidth - $watermarkWidth - $margin, $imageHeight - $watermarkHeight - $margin],
            'center' => [
                intval(($imageWidth - $watermarkWidth) / 2), 
                intval(($imageHeight - $watermarkHeight) / 2)
            ],
            default => [$imageWidth - $watermarkWidth - $margin, $imageHeight - $watermarkHeight - $margin]
        };
    }

    /**
     * Calculate new dimensions maintaining aspect ratio
     */
    private function calculateNewDimensions(int $originalWidth, int $originalHeight, ?int $maxWidth, ?int $maxHeight, bool $maintainAspectRatio): array
    {
        if (!$maintainAspectRatio) {
            return [$maxWidth ?: $originalWidth, $maxHeight ?: $originalHeight];
        }

        $aspectRatio = $originalWidth / $originalHeight;

        if ($maxWidth && $maxHeight) {
            if ($originalWidth / $maxWidth > $originalHeight / $maxHeight) {
                return [$maxWidth, intval($maxWidth / $aspectRatio)];
            } else {
                return [intval($maxHeight * $aspectRatio), $maxHeight];
            }
        } elseif ($maxWidth) {
            return [$maxWidth, intval($maxWidth / $aspectRatio)];
        } elseif ($maxHeight) {
            return [intval($maxHeight * $aspectRatio), $maxHeight];
        }

        return [$originalWidth, $originalHeight];
    }

    /**
     * Create image resource from file
     */
    private function createImageResource(string $imagePath, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($imagePath),
            'image/png' => imagecreatefrompng($imagePath),
            'image/gif' => imagecreatefromgif($imagePath),
            'image/webp' => imagecreatefromwebp($imagePath),
            default => throw new \InvalidArgumentException("Unsupported image type: {$mimeType}")
        };
    }

    /**
     * Save image to file
     */
    private function saveImage($image, string $outputPath, string $mimeType, int $quality = 85): void
    {
        // Ensure directory exists
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $outputPath, $quality),
            'image/png' => imagepng($image, $outputPath, 9 - intval($quality / 11)),
            'image/gif' => imagegif($image, $outputPath),
            'image/webp' => imagewebp($image, $outputPath, $quality),
            default => throw new \InvalidArgumentException("Unsupported output type: {$mimeType}")
        };
    }

    /**
     * Preserve transparency for PNG/GIF images
     */
    private function preserveTransparency($newImage, $originalImage, string $mimeType): void
    {
        if ($mimeType === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefill($newImage, 0, 0, $transparent);
        } elseif ($mimeType === 'image/gif') {
            $transparentIndex = imagecolortransparent($originalImage);
            if ($transparentIndex >= 0) {
                $transparentColor = imagecolorsforindex($originalImage, $transparentIndex);
                $transparentNew = imagecolorallocate($newImage, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
                imagefill($newImage, 0, 0, $transparentNew);
                imagecolortransparent($newImage, $transparentNew);
            }
        }
    }

    /**
     * Apply opacity to an image
     */
    private function applyOpacityToImage($image, int $opacity): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Create a transparent overlay
        $overlay = imagecreatetruecolor($width, $height);
        imagealphablending($overlay, false);
        imagesavealpha($overlay, true);
        
        $transparentColor = imagecolorallocatealpha($overlay, 255, 255, 255, 127 - ($opacity * 1.27));
        imagefill($overlay, 0, 0, $transparentColor);
        
        imagecopymerge($image, $overlay, 0, 0, 0, 0, $width, $height, $opacity);
        imagedestroy($overlay);
    }

    /**
     * Convert hex color to RGB array
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * Get default font path
     */
    private function getDefaultFont(): string
    {
        // Try to use a system font, fallback to built-in
        $fonts = [
            '/System/Library/Fonts/Arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/Windows/Fonts/arial.ttf'
        ];

        foreach ($fonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }

        // Use built-in font (not TTF, limited functionality)
        return '';
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