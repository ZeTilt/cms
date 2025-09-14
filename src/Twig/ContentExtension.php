<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('extract_images', [$this, 'extractImages']),
            new TwigFunction('extract_carousel', [$this, 'extractCarousel']),
            new TwigFunction('has_media', [$this, 'hasMedia']),
        ];
    }

    /**
     * Extract all images from article content (including carousels and regular images)
     */
    public function extractImages(string $content): array
    {
        $images = [];
        
        // Extract images from carousel shortcodes
        $carouselImages = $this->extractCarouselImages($content);
        $images = array_merge($images, $carouselImages);
        
        // Extract regular img tags
        preg_match_all('/<img[^>]+src="([^"]+)"[^>]*>/i', $content, $matches);
        if (!empty($matches[1])) {
            $images = array_merge($images, $matches[1]);
        }
        
        // Remove duplicates and empty values
        $images = array_unique(array_filter($images));
        
        return array_values($images);
    }

    /**
     * Extract images specifically from carousel shortcodes
     */
    public function extractCarouselImages(string $content): array
    {
        $images = [];
        
        // Find all carousel shortcodes
        preg_match_all('/\[carousel\](.*?)\[\/carousel\]/s', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $imageList) {
                $carouselImages = array_map('trim', explode(',', $imageList));
                $carouselImages = array_filter($carouselImages); // Remove empty values
                $images = array_merge($images, $carouselImages);
            }
        }
        
        return $images;
    }

    /**
     * Extract the first carousel from content
     */
    public function extractCarousel(string $content): ?array
    {
        // Find the first carousel shortcode
        preg_match('/\[carousel\](.*?)\[\/carousel\]/s', $content, $matches);
        
        if (!empty($matches[1])) {
            $images = array_map('trim', explode(',', $matches[1]));
            $images = array_filter($images); // Remove empty values
            
            if (count($images) > 0) {
                return array_values($images);
            }
        }
        
        return null;
    }

    /**
     * Check if content has any media (images or carousels)
     */
    public function hasMedia(string $content): bool
    {
        $images = $this->extractImages($content);
        return count($images) > 0;
    }
}