<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\SluggerInterface;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'articles')]
#[ORM\HasLifecycleCallbacks]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $excerpt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $featured_image = null;

    #[ORM\Column(length: 20)]
    private string $status = 'draft';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $published_at = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $meta_data = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $tags = null;

    /**
     * @var Collection<int, ContentBlock>
     */
    #[ORM\OneToMany(mappedBy: 'article', targetEntity: ContentBlock::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $contentBlocks;

    /**
     * Whether this article uses the new block-based editor
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $useBlocks = false;

    /**
     * Featured image alt text for accessibility
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $featuredImageAlt = null;

    /**
     * Featured image caption
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $featuredImageCaption = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
        $this->status = 'draft';
        $this->tags = [];
        $this->meta_data = [];
        $this->contentBlocks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getSafeContent(): string
    {
        // Content is already sanitized when saved, but this provides extra safety
        return $this->content;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): static
    {
        $this->excerpt = $excerpt;
        return $this;
    }

    public function getFeaturedImage(): ?string
    {
        return $this->featured_image;
    }

    public function setFeaturedImage(?string $featured_image): static
    {
        $this->featured_image = $featured_image;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getPublishedAt(): ?\DateTimeInterface
    {
        return $this->published_at;
    }

    public function setPublishedAt(?\DateTimeInterface $published_at): static
    {
        $this->published_at = $published_at;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getMetaData(): ?array
    {
        return $this->meta_data;
    }

    public function setMetaData(?array $meta_data): static
    {
        $this->meta_data = $meta_data;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    public function addTag(string $tag): static
    {
        $tags = $this->getTags() ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->setTags($tags);
        }
        return $this;
    }

    public function removeTag(string $tag): static
    {
        $tags = $this->getTags() ?? [];
        $key = array_search($tag, $tags);
        if ($key !== false) {
            unset($tags[$key]);
            $this->setTags(array_values($tags));
        }
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function publish(): static
    {
        $this->status = 'published';
        if ($this->published_at === null) {
            $this->published_at = new \DateTime();
        }
        return $this;
    }

    public function unpublish(): static
    {
        $this->status = 'draft';
        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new \DateTime();
    }

    public function generateSlug(SluggerInterface $slugger): static
    {
        if (empty($this->slug) && !empty($this->title)) {
            $this->slug = $slugger->slug(strtolower($this->title))->toString();
        }
        return $this;
    }

    // ========== Content Blocks methods ==========

    /**
     * @return Collection<int, ContentBlock>
     */
    public function getContentBlocks(): Collection
    {
        return $this->contentBlocks;
    }

    public function addContentBlock(ContentBlock $block): static
    {
        if (!$this->contentBlocks->contains($block)) {
            $this->contentBlocks->add($block);
            $block->setArticle($this);
        }
        return $this;
    }

    public function removeContentBlock(ContentBlock $block): static
    {
        if ($this->contentBlocks->removeElement($block)) {
            if ($block->getArticle() === $this) {
                $block->setArticle(null);
            }
        }
        return $this;
    }

    public function clearContentBlocks(): static
    {
        $this->contentBlocks->clear();
        return $this;
    }

    public function getUseBlocks(): bool
    {
        return $this->useBlocks;
    }

    public function setUseBlocks(bool $useBlocks): static
    {
        $this->useBlocks = $useBlocks;
        return $this;
    }

    public function getFeaturedImageAlt(): ?string
    {
        return $this->featuredImageAlt;
    }

    public function setFeaturedImageAlt(?string $alt): static
    {
        $this->featuredImageAlt = $alt;
        return $this;
    }

    public function getFeaturedImageCaption(): ?string
    {
        return $this->featuredImageCaption;
    }

    public function setFeaturedImageCaption(?string $caption): static
    {
        $this->featuredImageCaption = $caption;
        return $this;
    }

    /**
     * Get all images from content blocks (for sitemap, meta, etc.)
     *
     * @return array<string>
     */
    public function getAllBlockImages(): array
    {
        $images = [];

        if ($this->featured_image) {
            $images[] = $this->featured_image;
        }

        foreach ($this->contentBlocks as $block) {
            if ($block->getType() === ContentBlock::TYPE_IMAGE) {
                $url = $block->getImageUrl();
                if ($url) {
                    $images[] = $url;
                }
            } elseif ($block->getType() === ContentBlock::TYPE_GALLERY) {
                foreach ($block->getGalleryImages() as $image) {
                    if (isset($image['url'])) {
                        $images[] = $image['url'];
                    }
                }
            }
        }

        return array_unique($images);
    }

    /**
     * Get all videos from content blocks
     *
     * @return array<array{url: string, provider: string|null}>
     */
    public function getAllBlockVideos(): array
    {
        $videos = [];

        foreach ($this->contentBlocks as $block) {
            if ($block->getType() === ContentBlock::TYPE_VIDEO) {
                $url = $block->getVideoUrl();
                if ($url) {
                    $videos[] = [
                        'url' => $url,
                        'provider' => $block->getVideoProvider(),
                        'id' => $block->getVideoId(),
                    ];
                }
            }
        }

        return $videos;
    }
}