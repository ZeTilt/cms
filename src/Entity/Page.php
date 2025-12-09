<?php

namespace App\Entity;

use App\Repository\PageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'pages')]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $excerpt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    private string $template_path = '';

    #[ORM\Column(length: 50)]
    private string $type = 'page'; // 'page' or 'blog'

    #[ORM\Column(length: 20)]
    private string $status = 'draft'; // 'draft', 'published', 'archived'

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $featuredImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(type: 'json')]
    private array $tags = [];

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $author;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $useBlocks = false;

    #[ORM\OneToMany(mappedBy: 'page', targetEntity: ContentBlock::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $contentBlocks;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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
        $this->generateSlug();
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

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): static
    {
        $this->excerpt = $excerpt;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getTemplatePath(): string
    {
        return $this->template_path;
    }

    public function setTemplatePath(string $template_path): static
    {
        $this->template_path = $template_path;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getFeaturedImage(): ?string
    {
        return $this->featuredImage;
    }

    public function setFeaturedImage(?string $featuredImage): static
    {
        $this->featuredImage = $featuredImage;
        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && 
               $this->publishedAt !== null && 
               $this->publishedAt <= new \DateTimeImmutable();
    }

    public function isPage(): bool
    {
        return $this->type === 'page';
    }

    public function isBlog(): bool
    {
        return $this->type === 'blog';
    }

    public function publish(): static
    {
        $this->status = 'published';
        if (!$this->publishedAt) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function generateSlug(): static
    {
        $slug = strtolower($this->title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $this->slug = $slug;
        return $this;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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

    /**
     * @return Collection<int, ContentBlock>
     */
    public function getContentBlocks(): Collection
    {
        return $this->contentBlocks;
    }

    public function addContentBlock(ContentBlock $contentBlock): static
    {
        if (!$this->contentBlocks->contains($contentBlock)) {
            $this->contentBlocks->add($contentBlock);
            $contentBlock->setPage($this);
        }
        return $this;
    }

    public function removeContentBlock(ContentBlock $contentBlock): static
    {
        if ($this->contentBlocks->removeElement($contentBlock)) {
            if ($contentBlock->getPage() === $this) {
                $contentBlock->setPage(null);
            }
        }
        return $this;
    }
}