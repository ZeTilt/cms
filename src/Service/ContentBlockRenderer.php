<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\ContentBlock;
use App\Entity\Page;
use Twig\Environment;

/**
 * Service to render content blocks with optimizations
 */
class ContentBlockRenderer
{
    private ?WidgetRenderer $widgetRenderer = null;

    public function __construct(
        private Environment $twig,
        private ImageOptimizerService $imageOptimizer,
        private string $projectDir
    ) {}

    public function setWidgetRenderer(WidgetRenderer $widgetRenderer): void
    {
        $this->widgetRenderer = $widgetRenderer;
    }

    /**
     * Render all content blocks for an article
     */
    public function renderArticleBlocks(Article $article): string
    {
        if (!$article->getUseBlocks()) {
            return $article->getContent();
        }

        $html = '';
        foreach ($article->getContentBlocks() as $block) {
            $html .= $this->renderBlock($block);
        }

        return $html;
    }

    /**
     * Render all content blocks for a page
     */
    public function renderPageBlocks(Page $page): string
    {
        if (!$page->getUseBlocks()) {
            return '';
        }

        $html = '';
        foreach ($page->getContentBlocks() as $block) {
            $html .= $this->renderBlock($block);
        }

        return $html;
    }

    /**
     * Render a single content block
     */
    public function renderBlock(ContentBlock $block): string
    {
        return match ($block->getType()) {
            ContentBlock::TYPE_TEXT => $this->renderTextBlock($block),
            ContentBlock::TYPE_IMAGE => $this->renderImageBlock($block),
            ContentBlock::TYPE_GALLERY => $this->renderGalleryBlock($block),
            ContentBlock::TYPE_VIDEO => $this->renderVideoBlock($block),
            ContentBlock::TYPE_QUOTE => $this->renderQuoteBlock($block),
            ContentBlock::TYPE_ACCORDION => $this->renderAccordionBlock($block),
            ContentBlock::TYPE_CTA => $this->renderCtaBlock($block),
            ContentBlock::TYPE_WIDGET => $this->renderWidgetBlock($block),
            ContentBlock::TYPE_ROW => $this->renderRowBlock($block),
            default => '',
        };
    }

    private function renderWidgetBlock(ContentBlock $block): string
    {
        if (!$this->widgetRenderer) {
            return '<div class="widget-error">Widget renderer non configuré</div>';
        }

        return $this->widgetRenderer->render($block);
    }

    private function renderTextBlock(ContentBlock $block): string
    {
        $content = $block->getContent();
        return sprintf('<div class="block-text prose max-w-none">%s</div>', $content);
    }

    private function renderImageBlock(ContentBlock $block): string
    {
        $url = $block->getImageUrl();
        if (!$url) {
            return '';
        }

        $alt = htmlspecialchars($block->getImageAlt(), ENT_QUOTES, 'UTF-8');
        $caption = $block->getImageCaption();
        $alignment = $block->getImageAlignment();
        $size = $block->getImageSize();

        // Generate optimized image URLs
        $srcset = $this->generateSrcset($url);
        $sizes = $this->getSizesAttribute($size);
        $thumbUrl = $this->getThumbUrl($url);

        // Alignment classes
        $alignClass = match ($alignment) {
            'left' => 'float-left mr-6 mb-4',
            'right' => 'float-right ml-6 mb-4',
            default => 'mx-auto',
        };

        // Size classes
        $sizeClass = match ($size) {
            'small' => 'max-w-xs',
            'medium' => 'max-w-md',
            'large' => 'max-w-2xl',
            default => 'w-full',
        };

        $html = sprintf(
            '<figure class="block-image %s %s my-6">',
            $alignClass,
            $sizeClass
        );

        $html .= sprintf(
            '<img src="%s" alt="%s" loading="lazy" decoding="async" class="rounded-lg w-full" %s %s>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $alt,
            $srcset ? sprintf('srcset="%s"', $srcset) : '',
            $sizes ? sprintf('sizes="%s"', $sizes) : ''
        );

        if ($caption) {
            $html .= sprintf(
                '<figcaption class="text-sm text-gray-600 text-center mt-2">%s</figcaption>',
                htmlspecialchars($caption, ENT_QUOTES, 'UTF-8')
            );
        }

        $html .= '</figure>';

        return $html;
    }

    private function renderGalleryBlock(ContentBlock $block): string
    {
        $images = $block->getGalleryImages();
        if (empty($images)) {
            return '';
        }

        $layout = $block->getGalleryLayout();
        $columns = $block->getGalleryColumns();

        if ($layout === 'carousel') {
            return $this->renderCarouselGallery($images);
        }

        // Grid or masonry layout
        $gridClass = match ($columns) {
            2 => 'grid-cols-2',
            3 => 'grid-cols-2 md:grid-cols-3',
            4 => 'grid-cols-2 md:grid-cols-4',
            default => 'grid-cols-3',
        };

        $html = sprintf('<div class="block-gallery grid %s gap-4 my-6">', $gridClass);

        foreach ($images as $image) {
            $url = $image['url'] ?? '';
            $alt = htmlspecialchars($image['alt'] ?? '', ENT_QUOTES, 'UTF-8');
            $thumbUrl = $this->getThumbUrl($url);

            $html .= sprintf(
                '<a href="%s" class="gallery-item block aspect-square overflow-hidden rounded-lg" data-lightbox="gallery">
                    <img src="%s" alt="%s" loading="lazy" decoding="async" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                </a>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($thumbUrl ?: $url, ENT_QUOTES, 'UTF-8'),
                $alt
            );
        }

        $html .= '</div>';

        return $html;
    }

    private function renderCarouselGallery(array $images): string
    {
        $urls = array_map(fn($img) => $img['url'] ?? '', $images);
        $urlsString = implode(',', $urls);

        return sprintf(
            '[carousel]%s[/carousel]',
            $urlsString
        );
    }

    private function renderVideoBlock(ContentBlock $block): string
    {
        $url = $block->getVideoUrl();
        if (!$url) {
            return '';
        }

        $caption = $block->getVideoCaption();
        $provider = $block->getVideoProvider();
        $videoId = $block->getVideoId();

        $html = '<figure class="block-video my-6">';

        if ($provider === 'youtube' && $videoId) {
            // Lazy load YouTube with thumbnail
            $thumbnailUrl = sprintf('https://img.youtube.com/vi/%s/maxresdefault.jpg', $videoId);
            $html .= sprintf(
                '<div class="video-container relative aspect-video bg-gray-900 rounded-lg overflow-hidden cursor-pointer youtube-facade" data-video-id="%s">
                    <img src="%s" alt="Vidéo YouTube" loading="lazy" class="w-full h-full object-cover">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <button class="play-button w-16 h-16 bg-red-600 rounded-full flex items-center justify-center hover:bg-red-700 transition-colors">
                            <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>',
                $videoId,
                $thumbnailUrl
            );
        } elseif ($provider === 'vimeo' && $videoId) {
            $html .= sprintf(
                '<div class="video-container aspect-video rounded-lg overflow-hidden">
                    <iframe src="https://player.vimeo.com/video/%s" class="w-full h-full" frameborder="0" allow="autoplay; fullscreen" allowfullscreen loading="lazy"></iframe>
                </div>',
                $videoId
            );
        } else {
            // Local video
            $html .= sprintf(
                '<div class="video-container aspect-video rounded-lg overflow-hidden">
                    <video src="%s" controls class="w-full h-full" preload="metadata"></video>
                </div>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        if ($caption) {
            $html .= sprintf(
                '<figcaption class="text-sm text-gray-600 text-center mt-2">%s</figcaption>',
                htmlspecialchars($caption, ENT_QUOTES, 'UTF-8')
            );
        }

        $html .= '</figure>';

        return $html;
    }

    private function renderQuoteBlock(ContentBlock $block): string
    {
        $text = $block->getQuoteText();
        if (!$text) {
            return '';
        }

        $author = $block->getQuoteAuthor();

        $html = '<blockquote class="block-quote border-l-4 border-club-orange pl-6 py-2 my-6 italic text-gray-700">';
        $html .= sprintf('<p class="text-lg">%s</p>', htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));

        if ($author) {
            $html .= sprintf(
                '<footer class="text-sm text-gray-500 mt-2 not-italic">— %s</footer>',
                htmlspecialchars($author, ENT_QUOTES, 'UTF-8')
            );
        }

        $html .= '</blockquote>';

        return $html;
    }

    private function renderAccordionBlock(ContentBlock $block): string
    {
        $items = $block->getAccordionItems();
        if (empty($items)) {
            return '';
        }

        $html = '<div class="block-accordion my-6 space-y-2" x-data="{ open: null }">';

        foreach ($items as $index => $item) {
            $title = htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $content = $item['content'] ?? '';

            $html .= sprintf(
                '<div class="accordion-item border border-gray-200 rounded-lg overflow-hidden">
                    <button type="button"
                            class="accordion-header w-full px-4 py-3 text-left font-medium text-gray-900 bg-gray-50 hover:bg-gray-100 flex items-center justify-between"
                            @click="open = open === %d ? null : %d">
                        <span>%s</span>
                        <svg class="w-5 h-5 transition-transform" :class="{ \'rotate-180\': open === %d }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="accordion-content px-4 py-3" x-show="open === %d" x-collapse>
                        %s
                    </div>
                </div>',
                $index, $index, $title, $index, $index, $content
            );
        }

        $html .= '</div>';

        return $html;
    }

    private function renderCtaBlock(ContentBlock $block): string
    {
        $text = $block->getCtaText();
        $url = $block->getCtaUrl();
        $style = $block->getCtaStyle();

        $buttonClass = match ($style) {
            'secondary' => 'bg-club-blue text-white px-4 py-2 rounded hover:bg-club-blue-dark',
            'outline' => 'border-2 border-club-orange text-club-orange px-4 py-2 rounded hover:bg-club-orange hover:text-white',
            default => 'bg-club-orange text-white px-4 py-2 rounded hover:bg-club-orange-dark',
        };

        return sprintf(
            '<div class="block-cta">
                <a href="%s" class="%s">
                    %s
                </a>
            </div>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $buttonClass,
            htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
    }

    private function renderRowBlock(ContentBlock $block): string
    {
        $data = $block->getData();
        $cells = $data['cells'] ?? [];
        $totalCols = (int) ($data['columns'] ?? 12);
        $gap = $data['gap'] ?? 'normal';
        $verticalAlign = $data['vertical_align'] ?? 'top';

        if (empty($cells)) {
            return '';
        }

        $gapClass = match ($gap) {
            'none' => 'gap-0',
            'small' => 'gap-2',
            'large' => 'gap-8',
            default => 'gap-4',
        };

        $alignClass = match ($verticalAlign) {
            'center' => 'items-center',
            'bottom' => 'items-end',
            'stretch' => 'items-stretch',
            default => 'items-start',
        };

        $html = sprintf(
            '<div class="block-row grid grid-cols-%d %s %s my-6">',
            $totalCols,
            $gapClass,
            $alignClass
        );

        foreach ($cells as $cell) {
            $colspan = (int) ($cell['colspan'] ?? 1);
            $cellContent = $this->renderCellContent($cell);
            $html .= sprintf(
                '<div class="block-cell col-span-%d">%s</div>',
                $colspan,
                $cellContent
            );
        }

        $html .= '</div>';

        return $html;
    }

    private function renderCellContent(array $cell): string
    {
        $type = $cell['type'] ?? 'text';
        $cellData = $cell['data'] ?? [];

        return match ($type) {
            'text' => $cellData['content'] ?? '',
            'image' => $this->renderCellImage($cellData),
            'widget' => $this->renderCellWidget($cellData),
            'cta' => $this->renderCellCta($cellData),
            default => '',
        };
    }

    private function renderCellImage(array $data): string
    {
        $url = $data['url'] ?? '';
        if (empty($url)) return '';

        $alt = htmlspecialchars($data['alt'] ?? '', ENT_QUOTES, 'UTF-8');
        $caption = $data['caption'] ?? '';

        $html = sprintf(
            '<figure class="block-cell-image"><img src="%s" alt="%s" loading="lazy" class="w-full h-auto rounded-lg">',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $alt
        );

        if ($caption) {
            $html .= sprintf('<figcaption class="text-sm text-gray-500 mt-2">%s</figcaption>', htmlspecialchars($caption, ENT_QUOTES, 'UTF-8'));
        }

        $html .= '</figure>';
        return $html;
    }

    private function renderCellWidget(array $data): string
    {
        if (!$this->widgetRenderer) {
            return '';
        }

        $widgetType = $data['widget_type'] ?? '';
        $config = $data['config'] ?? [];

        if (empty($widgetType)) return '';

        // Create a temporary block-like structure for the widget renderer
        $tempBlock = new ContentBlock();
        $tempBlock->setType(ContentBlock::TYPE_WIDGET);
        $tempBlock->setData(['widget_type' => $widgetType, 'config' => $config]);

        return $this->widgetRenderer->render($tempBlock);
    }

    private function renderCellCta(array $data): string
    {
        $text = $data['text'] ?? 'En savoir plus';
        $url = $data['url'] ?? '#';
        $style = $data['style'] ?? 'primary';

        $buttonClass = match ($style) {
            'secondary' => 'bg-club-blue text-white px-4 py-2 rounded hover:bg-club-blue-dark',
            'outline' => 'border-2 border-club-orange text-club-orange px-4 py-2 rounded hover:bg-club-orange hover:text-white',
            default => 'bg-club-orange text-white px-4 py-2 rounded hover:bg-club-orange-dark',
        };

        return sprintf(
            '<div class="block-cell-cta"><a href="%s" class="%s">%s</a></div>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $buttonClass,
            htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Generate srcset for responsive images
     */
    private function generateSrcset(string $url): string
    {
        // Check if it's a local image
        if (!str_starts_with($url, '/')) {
            return '';
        }

        $srcset = [];
        $basePath = $this->projectDir . '/public' . $url;

        if (!file_exists($basePath)) {
            return '';
        }

        // Check for WebP version
        $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $basePath);
        if (file_exists($webpPath)) {
            $webpUrl = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $url);
            // Get image width
            $imageInfo = @getimagesize($basePath);
            if ($imageInfo) {
                $srcset[] = $webpUrl . ' ' . $imageInfo[0] . 'w';
            }
        }

        // Check for thumbnail
        $thumbPath = preg_replace('/\.(\w+)$/', '_thumb.$1', $basePath);
        $thumbWebpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $thumbPath);

        if (file_exists($thumbWebpPath)) {
            $thumbWebpUrl = preg_replace('/\.(\w+)$/', '_thumb.$1', $url);
            $thumbWebpUrl = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $thumbWebpUrl);
            $srcset[] = $thumbWebpUrl . ' 800w';
        } elseif (file_exists($thumbPath)) {
            $thumbUrl = preg_replace('/\.(\w+)$/', '_thumb.$1', $url);
            $srcset[] = $thumbUrl . ' 800w';
        }

        return implode(', ', $srcset);
    }

    /**
     * Get sizes attribute based on image size setting
     */
    private function getSizesAttribute(string $size): string
    {
        return match ($size) {
            'small' => '(max-width: 320px) 100vw, 320px',
            'medium' => '(max-width: 448px) 100vw, 448px',
            'large' => '(max-width: 672px) 100vw, 672px',
            default => '100vw',
        };
    }

    /**
     * Get thumbnail URL for an image
     */
    private function getThumbUrl(string $url): string
    {
        if (!str_starts_with($url, '/')) {
            return $url;
        }

        $thumbUrl = preg_replace('/\.(\w+)$/', '_thumb.$1', $url);
        $thumbPath = $this->projectDir . '/public' . $thumbUrl;

        // Prefer WebP thumbnail
        $thumbWebpUrl = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $thumbUrl);
        $thumbWebpPath = $this->projectDir . '/public' . $thumbWebpUrl;

        if (file_exists($thumbWebpPath)) {
            return $thumbWebpUrl;
        }

        if (file_exists($thumbPath)) {
            return $thumbUrl;
        }

        return $url;
    }
}
