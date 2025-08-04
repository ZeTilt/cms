<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Gallery;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecureImageUrlGenerator
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function generateSecureImageUrl(Image $image, ?string $accessCode = null): string
    {
        $gallery = $image->getGallery();
        $params = [
            'galleryId' => $gallery->getId(),
            'filename' => $image->getFilename()
        ];

        if ($accessCode) {
            $params['code'] = $accessCode;
        }

        return $this->urlGenerator->generate('secure_gallery_image', $params);
    }

    public function generateSecureThumbnailUrl(Image $image, ?string $accessCode = null): string
    {
        $gallery = $image->getGallery();
        $params = [
            'galleryId' => $gallery->getId(),
            'filename' => $image->getFilename()
        ];

        if ($accessCode) {
            $params['code'] = $accessCode;
        }

        return $this->urlGenerator->generate('secure_gallery_thumbnail', $params);
    }

    public function generatePublicImageUrl(Image $image): string
    {
        // For public images, we can still serve them directly if needed
        // or always use secure URLs for consistency
        return $this->generateSecureImageUrl($image);
    }

    public function generatePublicThumbnailUrl(Image $image): string
    {
        return $this->generateSecureThumbnailUrl($image);
    }
}