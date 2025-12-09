<?php

namespace App\Service;

use App\Entity\ContentBlock;
use App\Repository\ArticleRepository;
use App\Repository\EventRepository;
use App\Repository\GalleryRepository;
use Twig\Environment;

/**
 * Service to render dynamic widgets in pages
 */
class WidgetRenderer
{
    public function __construct(
        private Environment $twig,
        private ArticleRepository $articleRepository,
        private EventRepository $eventRepository,
        private GalleryRepository $galleryRepository,
        private string $projectDir
    ) {}

    /**
     * Render a widget block
     */
    public function render(ContentBlock $block): string
    {
        if ($block->getType() !== ContentBlock::TYPE_WIDGET) {
            return '';
        }

        $widgetType = $block->getWidgetType();
        $config = $block->getWidgetConfig();

        return match ($widgetType) {
            ContentBlock::WIDGET_BLOG => $this->renderBlogWidget($config),
            ContentBlock::WIDGET_CALENDAR => $this->renderCalendarWidget($config),
            ContentBlock::WIDGET_PARTNERS => $this->renderPartnersWidget($config),
            ContentBlock::WIDGET_CONTACT => $this->renderContactWidget($config),
            ContentBlock::WIDGET_PRICING => $this->renderPricingWidget($config),
            ContentBlock::WIDGET_MAP => $this->renderMapWidget($config),
            ContentBlock::WIDGET_GALLERY => $this->renderGalleryWidget($config),
            default => '',
        };
    }

    /**
     * Render latest blog articles
     */
    private function renderBlogWidget(array $config): string
    {
        $limit = (int) ($config['limit'] ?? 3);
        $category = $config['category'] ?? null;

        $articles = $this->articleRepository->findLatestPublished($limit, $category);

        return $this->twig->render('widgets/blog.html.twig', [
            'articles' => $articles,
            'config' => $config,
        ]);
    }

    /**
     * Render upcoming events calendar
     */
    private function renderCalendarWidget(array $config): string
    {
        $limit = (int) ($config['limit'] ?? 5);

        $events = $this->eventRepository->findUpcomingEvents($limit);

        return $this->twig->render('widgets/calendar.html.twig', [
            'events' => $events,
            'config' => $config,
        ]);
    }

    /**
     * Render partners logos
     */
    private function renderPartnersWidget(array $config): string
    {
        $layout = $config['layout'] ?? 'grid'; // grid or carousel

        // Partners are stored as images in /public/uploads/partenaires/
        $partnersDir = $this->projectDir . '/public/uploads/partenaires';
        $partners = [];

        if (is_dir($partnersDir)) {
            $files = glob($partnersDir . '/*.{jpg,jpeg,png,gif,webp,svg}', GLOB_BRACE);
            foreach ($files as $file) {
                $partners[] = [
                    'image' => '/uploads/partenaires/' . basename($file),
                    'name' => pathinfo($file, PATHINFO_FILENAME),
                ];
            }
        }

        return $this->twig->render('widgets/partners.html.twig', [
            'partners' => $partners,
            'config' => $config,
        ]);
    }

    /**
     * Render contact form
     */
    private function renderContactWidget(array $config): string
    {
        return $this->twig->render('widgets/contact.html.twig', [
            'config' => $config,
        ]);
    }

    /**
     * Render pricing table
     */
    private function renderPricingWidget(array $config): string
    {
        $year = $config['year'] ?? date('Y');

        return $this->twig->render('widgets/pricing.html.twig', [
            'year' => $year,
            'config' => $config,
        ]);
    }

    /**
     * Render map
     */
    private function renderMapWidget(array $config): string
    {
        $address = $config['address'] ?? 'Vannes, France';
        $zoom = (int) ($config['zoom'] ?? 14);

        return $this->twig->render('widgets/map.html.twig', [
            'address' => $address,
            'zoom' => $zoom,
            'config' => $config,
        ]);
    }

    /**
     * Render image gallery
     */
    private function renderGalleryWidget(array $config): string
    {
        $layout = $config['layout'] ?? 'grid';
        $columns = (int) ($config['columns'] ?? 3);
        $images = [];
        $gallery = null;

        // Check if a gallery ID is specified
        $galleryId = $config['gallery_id'] ?? null;
        if ($galleryId) {
            $gallery = $this->galleryRepository->find($galleryId);
            if ($gallery) {
                foreach ($gallery->getImages() as $image) {
                    $images[] = [
                        'url' => $image->getUrl(),
                        'thumbnail' => $image->getThumbnailUrl(),
                        'alt' => $image->getAlt() ?? $image->getOriginalName() ?? '',
                        'caption' => $image->getCaption(),
                    ];
                }
            }
        } else {
            // Fallback: Get images from folder
            $folder = $config['folder'] ?? 'gallery';
            $galleryDir = $this->projectDir . '/public/uploads/' . $folder;

            if (is_dir($galleryDir)) {
                $files = glob($galleryDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
                foreach ($files as $file) {
                    $url = '/uploads/' . $folder . '/' . basename($file);
                    $images[] = [
                        'url' => $url,
                        'thumbnail' => $url,
                        'alt' => pathinfo($file, PATHINFO_FILENAME),
                        'title' => null,
                    ];
                }
            }
        }

        return $this->twig->render('widgets/gallery.html.twig', [
            'images' => $images,
            'gallery' => $gallery,
            'layout' => $layout,
            'columns' => $columns,
            'config' => $config,
        ]);
    }

    /**
     * Get all galleries for widget selection
     */
    public function getAvailableGalleries(): array
    {
        return $this->galleryRepository->findBy([], ['title' => 'ASC']);
    }
}
