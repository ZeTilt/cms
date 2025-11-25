<?php

namespace App\Twig;

use App\Service\PageContentRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class ContentExtension extends AbstractExtension
{
    public function __construct(
        private PageContentRenderer $pageContentRenderer,
        private string $projectDir
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('extract_images', [$this, 'extractImages']),
            new TwigFunction('extract_carousel', [$this, 'extractCarousel']),
            new TwigFunction('has_media', [$this, 'hasMedia']),
            new TwigFunction('youtube_thumbnail', [$this, 'getYoutubeThumbnail']),
            new TwigFunction('youtube_thumbnail_with_fallback', [$this, 'getYoutubeThumbnailWithFallback'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('render_page_content', [$this, 'renderPageContent'], ['is_safe' => ['html']]),
            new TwigFilter('youtube_thumbnails', [$this, 'replaceYoutubeThumbnails'], ['is_safe' => ['html']]),
            new TwigFilter('lazy_images', [$this, 'addLazyLoadingToImages'], ['is_safe' => ['html']]),
            new TwigFilter('webp', [$this, 'toWebp']),
            new TwigFilter('webp_thumb', [$this, 'toWebpThumb']),
        ];
    }

    /**
     * Convert image URL to WebP if available, otherwise return original URL
     */
    public function toWebp(string $imageUrl): string
    {
        return $this->getWebpUrl($imageUrl) ?? $imageUrl;
    }

    /**
     * Convert image URL to WebP thumbnail if available, fallback to regular WebP, then original
     */
    public function toWebpThumb(string $imageUrl): string
    {
        return $this->getWebpThumbUrl($imageUrl) ?? $this->getWebpUrl($imageUrl) ?? $imageUrl;
    }

    public function renderPageContent(string $content): string
    {
        return $this->pageContentRenderer->renderContent($content);
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
        
        // Extract YouTube thumbnails
        $youtubeThumbnails = $this->extractYoutubeThumbnails($content);
        $images = array_merge($images, $youtubeThumbnails);
        
        // Remove duplicates and empty values
        $images = array_unique(array_filter($images));
        
        return array_values($images);
    }

    /**
     * Extract YouTube thumbnails from content
     */
    public function extractYoutubeThumbnails(string $content): array
    {
        $thumbnails = [];
        $pattern = '/https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/';
        
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $videoId) {
                // Try different thumbnail qualities, starting with the best
                $thumbnailOptions = [
                    "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
                    "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
                    "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg",
                    "https://img.youtube.com/vi/{$videoId}/default.jpg"
                ];
                
                // For extraction, we'll use the first option (maxres) as the fallback will be handled by JS
                $thumbnails[] = $thumbnailOptions[0];
            }
        }
        
        return $thumbnails;
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

    /**
     * Extract YouTube video ID from URL
     */
    public function getYoutubeVideoId(string $url): ?string
    {
        $patterns = [
            '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/',
            '/(?:https?:\/\/)?(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Get YouTube thumbnail URL from video URL
     */
    public function getYoutubeThumbnail(string $url): ?string
    {
        $videoId = $this->getYoutubeVideoId($url);
        if ($videoId) {
            return "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
        }
        return null;
    }

    /**
     * Generate YouTube thumbnail image tag with fallback attributes
     */
    public function getYoutubeThumbnailWithFallback(string $imageUrl, string $altText, string $cssClasses = ''): string
    {
        // Check if this is a YouTube thumbnail URL
        if (preg_match('/img\.youtube\.com\/vi\/([a-zA-Z0-9_-]+)\//', $imageUrl, $matches)) {
            $videoId = $matches[1];

            // Multiple thumbnail options in order of preference
            $thumbnailOptions = [
                "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
                "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
                "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg",
                "https://img.youtube.com/vi/{$videoId}/default.jpg"
            ];

            $thumbnailOptionsJson = htmlspecialchars(json_encode($thumbnailOptions), ENT_QUOTES, 'UTF-8');

            return '<img src="' . $thumbnailOptions[0] . '"
                         alt="' . htmlspecialchars($altText, ENT_QUOTES, 'UTF-8') . '"
                         class="' . htmlspecialchars($cssClasses, ENT_QUOTES, 'UTF-8') . ' youtube-thumbnail"
                         loading="lazy"
                         data-fallback-urls=\'' . $thumbnailOptionsJson . '\'
                         data-current-index="0"
                         onerror="handleYouTubeThumbnailError(this)">';
        } else {
            // Regular image - check if WebP version exists
            $webpUrl = $this->getWebpUrl($imageUrl);
            $finalUrl = $webpUrl ?? $imageUrl;

            return '<img src="' . htmlspecialchars($finalUrl, ENT_QUOTES, 'UTF-8') . '"
                         alt="' . htmlspecialchars($altText, ENT_QUOTES, 'UTF-8') . '"
                         class="' . htmlspecialchars($cssClasses, ENT_QUOTES, 'UTF-8') . '"
                         loading="lazy">';
        }
    }

    /**
     * Get WebP URL if the WebP version exists, otherwise return null
     */
    private function getWebpUrl(string $imageUrl): ?string
    {
        // Only process local images (starting with /)
        if (!str_starts_with($imageUrl, '/')) {
            return null;
        }

        // Build WebP path
        $pathInfo = pathinfo($imageUrl);
        $extension = strtolower($pathInfo['extension'] ?? '');

        // Only convert jpg, jpeg, png
        if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return null;
        }

        $webpUrl = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
        $webpPath = $this->projectDir . '/public' . $webpUrl;

        // Check if WebP file exists
        if (file_exists($webpPath)) {
            return $webpUrl;
        }

        return null;
    }

    /**
     * Get WebP thumbnail URL if it exists, otherwise return null
     */
    private function getWebpThumbUrl(string $imageUrl): ?string
    {
        // Only process local images (starting with /)
        if (!str_starts_with($imageUrl, '/')) {
            return null;
        }

        // Build thumbnail WebP path
        $pathInfo = pathinfo($imageUrl);
        $extension = strtolower($pathInfo['extension'] ?? '');

        // Only convert jpg, jpeg, png
        if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return null;
        }

        $thumbUrl = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.webp';
        $thumbPath = $this->projectDir . '/public' . $thumbUrl;

        // Check if thumbnail WebP file exists
        if (file_exists($thumbPath)) {
            return $thumbUrl;
        }

        return null;
    }

    /**
     * Add loading="lazy" to all images in HTML content
     */
    public function addLazyLoadingToImages(string $content): string
    {
        // Add loading="lazy" to img tags that don't already have it
        return preg_replace_callback(
            '/<img\s+([^>]*)>/i',
            function ($matches) {
                $attributes = $matches[1];

                // Check if loading attribute already exists
                if (preg_match('/loading\s*=/i', $attributes)) {
                    return $matches[0];
                }

                // Add loading="lazy" attribute
                return '<img ' . $attributes . ' loading="lazy">';
            },
            $content
        );
    }

    /**
     * Replace YouTube links with thumbnails in content
     */
    public function replaceYoutubeThumbnails(string $content): string
    {
        // Pattern to match YouTube links
        $pattern = '/https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/';
        
        return preg_replace_callback($pattern, function($matches) {
            $videoId = $matches[1];
            $videoUrl = $matches[0];
            
            // Multiple thumbnail options in order of preference
            $thumbnailOptions = [
                "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
                "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
                "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg",
                "https://img.youtube.com/vi/{$videoId}/default.jpg"
            ];
            
            $thumbnailOptionsJson = json_encode($thumbnailOptions);
            
            return '<div class="youtube-thumbnail-container relative inline-block mb-4">
                <a href="' . $videoUrl . '" target="_blank" class="block relative group">
                    <img src="' . $thumbnailOptions[0] . '" 
                         alt="Vidéo YouTube" 
                         class="rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 max-w-full h-auto youtube-thumbnail"
                         data-fallback-urls=\'' . $thumbnailOptionsJson . '\'
                         data-current-index="0"
                         onerror="handleYouTubeThumbnailError(this)">
                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 group-hover:bg-opacity-50 transition-all duration-300 rounded-lg">
                        <div class="bg-red-600 rounded-full p-3 transform group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                            </svg>
                        </div>
                    </div>
                </a>
            </div>';
        }, $content);
    }
}