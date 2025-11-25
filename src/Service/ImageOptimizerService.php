<?php

namespace App\Service;

/**
 * Service d'optimisation des images
 * - Compression JPG/PNG
 * - Conversion en WebP
 * - Redimensionnement optionnel
 */
class ImageOptimizerService
{
    private const DEFAULT_JPEG_QUALITY = 75;
    private const DEFAULT_WEBP_QUALITY = 80;
    private const DEFAULT_PNG_COMPRESSION = 6;

    public function __construct(
        private string $projectDir
    ) {
    }

    /**
     * Optimise une image : compresse et crée une version WebP
     *
     * @param string $imagePath Chemin absolu vers l'image
     * @param int|null $maxWidth Largeur max (optionnel)
     * @param int|null $maxHeight Hauteur max (optionnel)
     * @return array ['original' => string, 'webp' => string|null, 'saved_bytes' => int]
     */
    public function optimize(string $imagePath, ?int $maxWidth = null, ?int $maxHeight = null): array
    {
        if (!file_exists($imagePath)) {
            throw new \InvalidArgumentException("File not found: $imagePath");
        }

        $originalSize = filesize($imagePath);
        $imageInfo = getimagesize($imagePath);

        if (!$imageInfo) {
            throw new \InvalidArgumentException("Invalid image file: $imagePath");
        }

        [$width, $height, $type] = $imageInfo;

        // Charger l'image selon son type
        $image = $this->loadImage($imagePath, $type);
        if (!$image) {
            throw new \RuntimeException("Could not load image: $imagePath");
        }

        // Redimensionner si nécessaire
        if ($maxWidth || $maxHeight) {
            $image = $this->resize($image, $width, $height, $maxWidth, $maxHeight);
            $newDimensions = [imagesx($image), imagesy($image)];
        } else {
            $newDimensions = [$width, $height];
        }

        // Optimiser et sauvegarder l'image originale
        $this->saveOptimized($image, $imagePath, $type);

        // Créer la version WebP
        $webpPath = $this->createWebpVersion($image, $imagePath);

        // Libérer la mémoire
        imagedestroy($image);

        $newSize = filesize($imagePath);
        $savedBytes = $originalSize - $newSize;

        return [
            'original' => $imagePath,
            'webp' => $webpPath,
            'original_size' => $originalSize,
            'new_size' => $newSize,
            'saved_bytes' => $savedBytes,
            'dimensions' => $newDimensions,
        ];
    }

    /**
     * Optimise toutes les images d'un dossier
     *
     * @return array Statistiques d'optimisation
     */
    public function optimizeDirectory(string $directory, ?int $maxWidth = null, ?int $maxHeight = null): array
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("Directory not found: $directory");
        }

        $results = [
            'processed' => 0,
            'skipped' => 0,
            'errors' => [],
            'total_saved' => 0,
            'files' => [],
        ];

        $extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $files = [];

        foreach ($extensions as $ext) {
            $files = array_merge($files, glob("$directory/*.$ext"));
            $files = array_merge($files, glob("$directory/*." . strtoupper($ext)));
        }

        foreach ($files as $file) {
            // Ignorer les fichiers déjà optimisés ou les thumbs
            if (str_contains($file, '_thumb') || str_contains($file, '.webp')) {
                $results['skipped']++;
                continue;
            }

            try {
                $result = $this->optimize($file, $maxWidth, $maxHeight);
                $results['processed']++;
                $results['total_saved'] += $result['saved_bytes'];
                $results['files'][] = [
                    'file' => basename($file),
                    'saved' => $result['saved_bytes'],
                    'webp' => $result['webp'] ? basename($result['webp']) : null,
                ];
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'file' => basename($file),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Charge une image selon son type
     */
    private function loadImage(string $path, int $type): ?\GdImage
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => null,
        };
    }

    /**
     * Redimensionne une image en conservant les proportions
     */
    private function resize(\GdImage $image, int $width, int $height, ?int $maxWidth, ?int $maxHeight): \GdImage
    {
        $ratio = $width / $height;

        $newWidth = $width;
        $newHeight = $height;

        if ($maxWidth && $width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) ($maxWidth / $ratio);
        }

        if ($maxHeight && $newHeight > $maxHeight) {
            $newHeight = $maxHeight;
            $newWidth = (int) ($maxHeight * $ratio);
        }

        if ($newWidth === $width && $newHeight === $height) {
            return $image;
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Préserver la transparence pour PNG
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $resized;
    }

    /**
     * Sauvegarde l'image optimisée
     */
    private function saveOptimized(\GdImage $image, string $path, int $type): void
    {
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $path, self::DEFAULT_JPEG_QUALITY),
            IMAGETYPE_PNG => imagepng($image, $path, self::DEFAULT_PNG_COMPRESSION),
            IMAGETYPE_GIF => imagegif($image, $path),
            IMAGETYPE_WEBP => imagewebp($image, $path, self::DEFAULT_WEBP_QUALITY),
            default => null,
        };
    }

    /**
     * Crée une version WebP de l'image
     */
    private function createWebpVersion(\GdImage $image, string $originalPath): ?string
    {
        $info = pathinfo($originalPath);
        $webpPath = $info['dirname'] . '/' . $info['filename'] . '.webp';

        if (imagewebp($image, $webpPath, self::DEFAULT_WEBP_QUALITY)) {
            return $webpPath;
        }

        return null;
    }

    /**
     * Formate une taille en bytes en format lisible
     */
    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' Mo';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' Ko';
        }

        return $bytes . ' octets';
    }
}
