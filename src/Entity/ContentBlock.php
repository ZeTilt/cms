<?php

namespace App\Entity;

use App\Repository\ContentBlockRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ContentBlock represents a block of content within an Article or Page.
 *
 * Supported block types:
 * - text: Rich text content (WYSIWYG)
 * - image: Single image with caption, alt text, alignment
 * - gallery: Multiple images in a grid/carousel
 * - video: YouTube, Vimeo URL or uploaded video
 * - quote: Blockquote with optional author
 * - accordion: Collapsible sections
 * - cta: Call-to-action button
 * - widget: Dynamic widget (blog, calendar, partners, etc.)
 */
#[ORM\Entity(repositoryClass: ContentBlockRepository::class)]
#[ORM\Table(name: 'content_blocks')]
#[ORM\HasLifecycleCallbacks]
class ContentBlock
{
    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_GALLERY = 'gallery';
    public const TYPE_VIDEO = 'video';
    public const TYPE_QUOTE = 'quote';
    public const TYPE_ACCORDION = 'accordion';
    public const TYPE_CTA = 'cta';
    public const TYPE_WIDGET = 'widget';
    public const TYPE_ROW = 'row';

    public const TYPES = [
        self::TYPE_TEXT => 'Texte',
        self::TYPE_IMAGE => 'Image',
        self::TYPE_GALLERY => 'Galerie',
        self::TYPE_VIDEO => 'Vidéo',
        self::TYPE_QUOTE => 'Citation',
        self::TYPE_ACCORDION => 'Accordéon',
        self::TYPE_CTA => 'Bouton d\'action',
        self::TYPE_WIDGET => 'Widget',
        self::TYPE_ROW => 'Ligne (grille)',
    ];

    // Widget types
    public const WIDGET_BLOG = 'blog';
    public const WIDGET_CALENDAR = 'calendar';
    public const WIDGET_PARTNERS = 'partners';
    public const WIDGET_CONTACT = 'contact';
    public const WIDGET_PRICING = 'pricing';
    public const WIDGET_MAP = 'map';
    public const WIDGET_GALLERY = 'gallery';

    public const WIDGETS = [
        self::WIDGET_BLOG => 'Derniers articles',
        self::WIDGET_CALENDAR => 'Prochains événements',
        self::WIDGET_PARTNERS => 'Partenaires',
        self::WIDGET_CONTACT => 'Formulaire de contact',
        self::WIDGET_PRICING => 'Tarifs',
        self::WIDGET_MAP => 'Carte de localisation',
        self::WIDGET_GALLERY => 'Galerie photos',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'contentBlocks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Article $article = null;

    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'contentBlocks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Page $page = null;

    #[ORM\Column(length: 50)]
    private string $type = self::TYPE_TEXT;

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $position = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->data = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;
        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        $this->page = $page;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!array_key_exists($type, self::TYPES)) {
            throw new \InvalidArgumentException(sprintf('Invalid block type "%s"', $type));
        }
        $this->type = $type;
        return $this;
    }

    public function getTypeName(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get a specific data value with optional default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set a specific data value
     */
    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // ========== Type-specific helper methods ==========

    /**
     * TEXT block: Get content
     */
    public function getContent(): string
    {
        return $this->get('content', '');
    }

    public function setContent(string $content): static
    {
        return $this->set('content', $content);
    }

    /**
     * IMAGE block: Get image URL
     */
    public function getImageUrl(): ?string
    {
        return $this->get('url');
    }

    public function setImageUrl(string $url): static
    {
        return $this->set('url', $url);
    }

    public function getImageAlt(): string
    {
        return $this->get('alt', '');
    }

    public function setImageAlt(string $alt): static
    {
        return $this->set('alt', $alt);
    }

    public function getImageCaption(): ?string
    {
        return $this->get('caption');
    }

    public function setImageCaption(?string $caption): static
    {
        return $this->set('caption', $caption);
    }

    public function getImageAlignment(): string
    {
        return $this->get('alignment', 'center');
    }

    public function setImageAlignment(string $alignment): static
    {
        return $this->set('alignment', $alignment);
    }

    public function getImageSize(): string
    {
        return $this->get('size', 'large');
    }

    public function setImageSize(string $size): static
    {
        return $this->set('size', $size);
    }

    /**
     * GALLERY block: Get images array
     */
    public function getGalleryImages(): array
    {
        return $this->get('images', []);
    }

    public function setGalleryImages(array $images): static
    {
        return $this->set('images', $images);
    }

    public function addGalleryImage(array $image): static
    {
        $images = $this->getGalleryImages();
        $images[] = $image;
        return $this->setGalleryImages($images);
    }

    public function getGalleryLayout(): string
    {
        return $this->get('layout', 'grid');
    }

    public function setGalleryLayout(string $layout): static
    {
        return $this->set('layout', $layout);
    }

    public function getGalleryColumns(): int
    {
        return $this->get('columns', 3);
    }

    public function setGalleryColumns(int $columns): static
    {
        return $this->set('columns', $columns);
    }

    /**
     * VIDEO block: Get video info
     */
    public function getVideoUrl(): ?string
    {
        return $this->get('url');
    }

    public function setVideoUrl(string $url): static
    {
        return $this->set('url', $url);
    }

    public function getVideoProvider(): ?string
    {
        $url = $this->getVideoUrl();
        if (!$url) return null;

        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }
        if (str_contains($url, 'vimeo.com')) {
            return 'vimeo';
        }
        return 'local';
    }

    public function getVideoId(): ?string
    {
        $url = $this->getVideoUrl();
        if (!$url) return null;

        $provider = $this->getVideoProvider();

        if ($provider === 'youtube') {
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
                return $matches[1];
            }
        }

        if ($provider === 'vimeo') {
            if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function getVideoCaption(): ?string
    {
        return $this->get('caption');
    }

    public function setVideoCaption(?string $caption): static
    {
        return $this->set('caption', $caption);
    }

    /**
     * QUOTE block: Get quote text
     */
    public function getQuoteText(): string
    {
        return $this->get('text', '');
    }

    public function setQuoteText(string $text): static
    {
        return $this->set('text', $text);
    }

    public function getQuoteAuthor(): ?string
    {
        return $this->get('author');
    }

    public function setQuoteAuthor(?string $author): static
    {
        return $this->set('author', $author);
    }

    /**
     * ACCORDION block: Get items
     */
    public function getAccordionItems(): array
    {
        return $this->get('items', []);
    }

    public function setAccordionItems(array $items): static
    {
        return $this->set('items', $items);
    }

    public function addAccordionItem(string $title, string $content): static
    {
        $items = $this->getAccordionItems();
        $items[] = ['title' => $title, 'content' => $content];
        return $this->setAccordionItems($items);
    }

    /**
     * CTA block: Get button info
     */
    public function getCtaText(): string
    {
        return $this->get('text', 'En savoir plus');
    }

    public function setCtaText(string $text): static
    {
        return $this->set('text', $text);
    }

    public function getCtaUrl(): string
    {
        return $this->get('url', '#');
    }

    public function setCtaUrl(string $url): static
    {
        return $this->set('url', $url);
    }

    public function getCtaStyle(): string
    {
        return $this->get('style', 'primary');
    }

    public function setCtaStyle(string $style): static
    {
        return $this->set('style', $style);
    }

    /**
     * WIDGET block: Get widget type
     */
    public function getWidgetType(): string
    {
        return $this->get('widget_type', self::WIDGET_BLOG);
    }

    public function setWidgetType(string $widgetType): static
    {
        return $this->set('widget_type', $widgetType);
    }

    public function getWidgetTypeName(): string
    {
        return self::WIDGETS[$this->getWidgetType()] ?? $this->getWidgetType();
    }

    /**
     * WIDGET block: Get widget config
     */
    public function getWidgetConfig(): array
    {
        return $this->get('config', []);
    }

    public function setWidgetConfig(array $config): static
    {
        return $this->set('config', $config);
    }

    public function getWidgetConfigValue(string $key, mixed $default = null): mixed
    {
        $config = $this->getWidgetConfig();
        return $config[$key] ?? $default;
    }

    public function setWidgetConfigValue(string $key, mixed $value): static
    {
        $config = $this->getWidgetConfig();
        $config[$key] = $value;
        return $this->setWidgetConfig($config);
    }
}
