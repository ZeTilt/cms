<?php

namespace App\Twig;

use App\Entity\Image;
use App\Service\SecureImageUrlGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SecureImageExtension extends AbstractExtension
{
    public function __construct(
        private SecureImageUrlGenerator $urlGenerator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('secure_image_url', [$this, 'getSecureImageUrl']),
            new TwigFunction('secure_thumbnail_url', [$this, 'getSecureThumbnailUrl']),
        ];
    }

    public function getSecureImageUrl(Image $image, ?string $accessCode = null): string
    {
        return $this->urlGenerator->generateSecureImageUrl($image, $accessCode);
    }

    public function getSecureThumbnailUrl(Image $image, ?string $accessCode = null): string
    {
        return $this->urlGenerator->generateSecureThumbnailUrl($image, $accessCode);
    }
}