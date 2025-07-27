<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'testimonials')]
class Testimonial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $clientName = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientCompany = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientEmail = null;

    #[ORM\Column(type: 'text')]
    private string $content = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shortContent = null;

    #[ORM\Column(nullable: true)]
    private ?int $rating = null; // 1-5 stars

    #[ORM\Column(length: 20)]
    private string $status = 'pending'; // pending, approved, rejected

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $category = null; // service, event, general

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $projectName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $projectDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientPhoto = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $projectImage = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tags = [];

    #[ORM\Column(type: 'boolean')]
    private bool $featured = false;

    #[ORM\Column(type: 'boolean')]
    private bool $allowPublicDisplay = true;

    #[ORM\Column(nullable: true)]
    private ?int $displayOrder = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Service $relatedService = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Event $relatedEvent = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $submittedBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientName(): string
    {
        return $this->clientName;
    }

    public function setClientName(string $clientName): static
    {
        $this->clientName = $clientName;
        return $this;
    }

    public function getClientTitle(): ?string
    {
        return $this->clientTitle;
    }

    public function setClientTitle(?string $clientTitle): static
    {
        $this->clientTitle = $clientTitle;
        return $this;
    }

    public function getClientCompany(): ?string
    {
        return $this->clientCompany;
    }

    public function setClientCompany(?string $clientCompany): static
    {
        $this->clientCompany = $clientCompany;
        return $this;
    }

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function setClientEmail(?string $clientEmail): static
    {
        $this->clientEmail = $clientEmail;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getShortContent(): ?string
    {
        return $this->shortContent;
    }

    public function setShortContent(?string $shortContent): static
    {
        $this->shortContent = $shortContent;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        $this->rating = $rating;
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
        
        if ($status === 'approved') {
            $this->approvedAt = new \DateTimeImmutable();
        }
        
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

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(?string $projectName): static
    {
        $this->projectName = $projectName;
        return $this;
    }

    public function getProjectDate(): ?\DateTimeImmutable
    {
        return $this->projectDate;
    }

    public function setProjectDate(?\DateTimeImmutable $projectDate): static
    {
        $this->projectDate = $projectDate;
        return $this;
    }

    public function getClientPhoto(): ?string
    {
        return $this->clientPhoto;
    }

    public function setClientPhoto(?string $clientPhoto): static
    {
        $this->clientPhoto = $clientPhoto;
        return $this;
    }

    public function getProjectImage(): ?string
    {
        return $this->projectImage;
    }

    public function setProjectImage(?string $projectImage): static
    {
        $this->projectImage = $projectImage;
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

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;
        return $this;
    }

    public function isAllowPublicDisplay(): bool
    {
        return $this->allowPublicDisplay;
    }

    public function setAllowPublicDisplay(bool $allowPublicDisplay): static
    {
        $this->allowPublicDisplay = $allowPublicDisplay;
        return $this;
    }

    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(?int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getRelatedService(): ?Service
    {
        return $this->relatedService;
    }

    public function setRelatedService(?Service $relatedService): static
    {
        $this->relatedService = $relatedService;
        return $this;
    }

    public function getRelatedEvent(): ?Event
    {
        return $this->relatedEvent;
    }

    public function setRelatedEvent(?Event $relatedEvent): static
    {
        $this->relatedEvent = $relatedEvent;
        return $this;
    }

    public function getSubmittedBy(): ?User
    {
        return $this->submittedBy;
    }

    public function setSubmittedBy(?User $submittedBy): static
    {
        $this->submittedBy = $submittedBy;
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

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    // Helper methods

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            default => $this->status
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getRatingStars(): string
    {
        if (!$this->rating) {
            return '';
        }
        
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $stars .= '★';
            } else {
                $stars .= '☆';
            }
        }
        return $stars;
    }

    public function getExcerpt(int $length = 150): string
    {
        if ($this->shortContent) {
            return $this->shortContent;
        }
        
        if (strlen($this->content) <= $length) {
            return $this->content;
        }
        
        return substr($this->content, 0, $length) . '...';
    }

    public function getClientFullName(): string
    {
        $name = $this->clientName;
        if ($this->clientTitle) {
            $name = $this->clientTitle . ' ' . $name;
        }
        if ($this->clientCompany) {
            $name .= ', ' . $this->clientCompany;
        }
        return $name;
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function canBeDisplayedPublicly(): bool
    {
        return $this->isApproved() && $this->allowPublicDisplay;
    }

    public function getRelatedItem(): ?object
    {
        return $this->relatedService ?? $this->relatedEvent;
    }

    public function getRelatedItemName(): ?string
    {
        if ($this->relatedService) {
            return $this->relatedService->getName();
        } elseif ($this->relatedEvent) {
            return $this->relatedEvent->getTitle();
        }
        return null;
    }

    public function getRelatedItemType(): ?string
    {
        if ($this->relatedService) {
            return 'service';
        } elseif ($this->relatedEvent) {
            return 'event';
        }
        return null;
    }
}