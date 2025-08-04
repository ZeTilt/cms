<?php

namespace App\Controller;

use App\Entity\Gallery;
use App\Service\ImageProcessingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[Route('/secure/images')]
class SecureImageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ImageProcessingService $imageProcessingService,
        private string $projectDir,
        private bool $watermarkEnabled = true,
        private bool $antiScrappingEnabled = true,
        private array $suspiciousAgents = [],
        private array $allowedAgents = [],
        private int $privateCacheMaxAge = 3600,
        private int $thumbnailCacheMaxAge = 7200,
        private string $watermarkTemplate = '{gallery_title} - {owner_name}',
        private string $watermarkType = 'text',
        private string $watermarkImagePath = 'public/assets/watermark.png',
        private int $watermarkOpacity = 70,
        private string $watermarkPosition = 'bottom-right',
        private int $watermarkMargin = 20,
        private int $watermarkScale = 100
    ) {
    }

    #[Route('/gallery/{galleryId}/{filename}', name: 'secure_gallery_image', methods: ['GET'])]
    public function serveGalleryImage(int $galleryId, string $filename, Request $request): Response
    {
        // Get gallery
        $gallery = $this->entityManager->getRepository(Gallery::class)->find($galleryId);
        if (!$gallery) {
            throw new NotFoundHttpException('Gallery not found');
        }

        // Check if gallery is active
        if (!$gallery->isActive()) {
            throw new AccessDeniedHttpException('Gallery is not active');
        }

        // Check expiration
        if ($gallery->getExpiresAt() && $gallery->getExpiresAt() < new \DateTimeImmutable()) {
            throw new AccessDeniedHttpException('Gallery has expired');
        }

        // Validate access based on gallery settings
        if (!$this->canAccessGallery($gallery, $request)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        // Construct file path - check both locations for backward compatibility 
        $filePath = $this->projectDir . '/public/uploads/galleries/' . $galleryId . '/' . $filename;
        if (!file_exists($filePath)) {
            $filePath = $this->projectDir . '/public/uploads/images/' . $filename;
        }
        
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new NotFoundHttpException('Image not found');
        }

        // Security checks on filename
        if ($this->isUnsafeFilename($filename)) {
            throw new AccessDeniedHttpException('Invalid filename');
        }

        // Check for suspicious access patterns
        if ($this->antiScrappingEnabled && $this->detectScrappingBehavior($request)) {
            throw new AccessDeniedHttpException('Suspicious activity detected');
        }

        // Apply watermark if needed and user is not owner/admin
        $shouldWatermark = $this->watermarkEnabled && $this->shouldApplyWatermark($gallery, $request);
        
        if ($shouldWatermark && $this->imageProcessingService->isGdAvailable()) {
            $watermarkOptions = $this->getWatermarkOptions($gallery);
            $watermarkText = $this->watermarkType === 'text' ? $this->generateWatermarkText($gallery) : null;
            
            $watermarkedImagePath = $this->imageProcessingService->addWatermark(
                $filePath,
                $watermarkText,
                null,
                $watermarkOptions
            );
            
            if ($watermarkedImagePath && file_exists($this->projectDir . '/' . $watermarkedImagePath)) {
                $watermarkedFullPath = $this->projectDir . '/' . $watermarkedImagePath;
                $response = new BinaryFileResponse($watermarkedFullPath);
                $response->headers->set('Content-Type', $this->getMimeType($filename));
                $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');
                $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename);
                return $response;
            }
        }

        // Serve original file with security headers
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $this->getMimeType($filename));
        $response->headers->set('Cache-Control', 'private, max-age=' . $this->privateCacheMaxAge);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename);

        return $response;
    }

    #[Route('/download/{galleryId}/{filename}', name: 'secure_gallery_download', methods: ['GET'])]
    public function downloadFullResolution(int $galleryId, string $filename, Request $request): Response
    {
        // Get gallery
        $gallery = $this->entityManager->getRepository(Gallery::class)->find($galleryId);
        if (!$gallery) {
            throw new NotFoundHttpException('Gallery not found');
        }

        // Check if gallery is active
        if (!$gallery->isActive()) {
            throw new AccessDeniedHttpException('Gallery is not active');
        }

        // Check expiration
        if ($gallery->getExpiresAt() && $gallery->getExpiresAt() < new \DateTimeImmutable()) {
            throw new AccessDeniedHttpException('Gallery has expired');
        }

        // Validate access - for downloads, require authentication or payment token
        if (!$this->canDownloadImage($gallery, $request)) {
            throw new AccessDeniedHttpException('Download access denied');
        }

        // Construct file path - check both locations for backward compatibility 
        $filePath = $this->projectDir . '/public/uploads/galleries/' . $galleryId . '/' . $filename;
        if (!file_exists($filePath)) {
            $filePath = $this->projectDir . '/public/uploads/images/' . $filename;
        }
        
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new NotFoundHttpException('Image not found');
        }

        // Security checks on filename
        if ($this->isUnsafeFilename($filename)) {
            throw new AccessDeniedHttpException('Invalid filename');
        }

        // NO WATERMARK for full resolution downloads - serve original file
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $this->getMimeType($filename));
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    private function canDownloadImage(Gallery $gallery, Request $request): bool
    {
        // Admin access
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Owner access
        if ($this->getUser() && $gallery->getOwner() === $this->getUser()) {
            return true;
        }

        // Check for valid payment/download token
        $downloadToken = $request->query->get('token');
        if ($downloadToken) {
            // TODO: Validate payment token against database
            // This would check if the user has purchased access to download this image
            // For now, we'll just require admin/owner access
            return false;
        }

        return false;
    }


    #[Route('/thumbnail/{galleryId}/{filename}', name: 'secure_gallery_thumbnail', methods: ['GET'])]
    public function serveThumbnail(int $galleryId, string $filename, Request $request): Response
    {
        // Same access checks as main image
        $gallery = $this->entityManager->getRepository(Gallery::class)->find($galleryId);
        if (!$gallery || !$gallery->isActive()) {
            throw new NotFoundHttpException('Gallery not found or inactive');
        }

        if (!$this->canAccessGallery($gallery, $request)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        // Generate or serve thumbnail - check both locations for backward compatibility
        $originalPath = $this->projectDir . '/public/uploads/galleries/' . $galleryId . '/' . $filename;
        if (!file_exists($originalPath)) {
            $originalPath = $this->projectDir . '/public/uploads/images/' . $filename;
        }
        
        if (!file_exists($originalPath)) {
            throw new NotFoundHttpException('Image not found');
        }

        // Pass the absolute path directly - the service will handle it
        $thumbnailData = $this->imageProcessingService->generateThumbnail($originalPath, 300, 200);
        
        if (!$thumbnailData) {
            throw new NotFoundHttpException('Cannot generate thumbnail');
        }

        // Apply watermark to thumbnail if needed
        $shouldWatermark = $this->watermarkEnabled && $this->shouldApplyWatermark($gallery, $request);
        
        if ($shouldWatermark && $this->imageProcessingService->isGdAvailable()) {
            $watermarkOptions = $this->getWatermarkOptions($gallery, true); // true for thumbnail
            $watermarkText = $this->watermarkType === 'text' ? $this->generateWatermarkText($gallery) : null;
            
            // Create a temporary file to apply watermark
            $tempFile = tempnam(sys_get_temp_dir(), 'thumb_') . '.jpg';
            file_put_contents($tempFile, $thumbnailData);
            
            $watermarkedThumbPath = $this->imageProcessingService->addWatermark(
                $tempFile,
                $watermarkText,
                null,
                $watermarkOptions
            );
            
            if ($watermarkedThumbPath && file_exists($this->projectDir . '/' . $watermarkedThumbPath)) {
                $thumbnailData = file_get_contents($this->projectDir . '/' . $watermarkedThumbPath);
                // Clean up temp files
                unlink($tempFile);
                unlink($this->projectDir . '/' . $watermarkedThumbPath);
            } else {
                unlink($tempFile);
            }
        }

        return new Response($thumbnailData, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=' . $this->thumbnailCacheMaxAge,
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function canAccessGallery(Gallery $gallery, Request $request): bool
    {
        // Admin access
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Owner access
        if ($this->getUser() && $gallery->getOwner() === $this->getUser()) {
            return true;
        }

        // Public gallery
        if ($gallery->isPublic()) {
            return true;
        }

        // Access code check
        if ($gallery->getAccessCode()) {
            $sessionKey = 'gallery_access_' . $gallery->getId();
            $providedCode = $request->query->get('code');
            
            if ($providedCode && hash_equals($gallery->getAccessCode(), $providedCode)) {
                $request->getSession()->set($sessionKey, true);
                return true;
            }
            
            return $request->getSession()->get($sessionKey, false);
        }

        // Private gallery - require authentication
        if ($this->getUser()) {
            return true;
        }

        return false;
    }

    private function shouldApplyWatermark(Gallery $gallery, Request $request): bool
    {
        // Check if this is a paid download (full resolution without watermark)
        // This would be handled by a separate route like /secure/images/download/{galleryId}/{filename}?token=...
        // For now, we always apply watermark to displayed images
        
        return true; // Always watermark displayed images
    }

    private function detectScrappingBehavior(Request $request): bool
    {
        $userAgent = $request->headers->get('User-Agent', '');
        $referer = $request->headers->get('Referer', '');
        $clientIp = $request->getClientIp();

        // Check for allowed agents first (SEO bots, social media)
        foreach ($this->allowedAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return false;
            }
        }

        // Check for suspicious user agents
        $suspiciousAgents = !empty($this->suspiciousAgents) ? 
            $this->suspiciousAgents : 
            ['wget', 'curl', 'python', 'scrapy', 'bot', 'crawler', 'spider', 'scraper', 'harvest', 'extract', 'collect'];

        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }

        // Check for missing referer (direct access to images)
        if (empty($referer) && !$this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Rate limiting could be implemented here
        // For now, we'll rely on web server rate limiting

        return false;
    }

    private function isUnsafeFilename(string $filename): bool
    {
        // Check for path traversal attempts
        if (strpos($filename, '..') !== false || 
            strpos($filename, '/') !== false || 
            strpos($filename, '\\') !== false) {
            return true;
        }

        // Only allow image extensions
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return !in_array($extension, $allowedExtensions);
    }

    private function getWatermarkOptions(Gallery $gallery, bool $isThumbnail = false): array
    {
        $options = [
            'position' => $this->watermarkPosition,
            'opacity' => $this->watermarkOpacity,
            'margin' => $this->watermarkMargin,
            'use_image_watermark' => $this->watermarkType === 'image'
        ];
        
        if ($this->watermarkType === 'image') {
            // For image watermarks, we need the full path
            $watermarkFullPath = $this->projectDir . '/' . $this->watermarkImagePath;
            if (file_exists($watermarkFullPath)) {
                $options['watermark_image_path'] = $watermarkFullPath;
                
                // Scale watermark for thumbnails
                if ($isThumbnail) {
                    $options['watermark_scale'] = max(30, $this->watermarkScale * 0.5); // Smaller for thumbnails
                } else {
                    $options['watermark_scale'] = $this->watermarkScale;
                }
            } else {
                // Fallback to text if image doesn't exist
                $options['use_image_watermark'] = false;
            }
        } else {
            // Text watermark options
            if ($isThumbnail) {
                $options['font_size'] = 10;
                $options['opacity'] = 60;
            } else {
                $options['font_size'] = 14;
            }
        }
        
        return $options;
    }

    private function generateWatermarkText(Gallery $gallery): string
    {
        $replacements = [
            '{gallery_title}' => $gallery->getTitle(),
            '{owner_name}' => $gallery->getOwner()->getFullName(),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $this->watermarkTemplate);
    }

    private function getMimeType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return match($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream'
        };
    }
}