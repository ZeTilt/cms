<?php

namespace App\Entity;

use App\Repository\GalleryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: GalleryRepository::class)]
class Gallery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column(length: 20, options: ['default' => 'public'])]
    private string $visibility = 'public';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $accessCode = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'galleries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\OneToMany(mappedBy: 'gallery', targetEntity: Image::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC', 'createdAt' => 'ASC'])]
    private Collection $images;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $durationDays = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'galleries')]
    private Collection $events;

    #[ORM\Column(length: 20, options: ['default' => 'free'])]
    private string $pricingType = 'free'; // 'free' or 'paid'

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $accessPrice = null;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        $this->generateSlug();
        
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function generateSlug(): void
    {
        if ($this->title) {
            $slugger = new AsciiSlugger();
            $this->slug = $slugger->slug($this->title)->lower();
        }
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getAccessCode(): ?string
    {
        return $this->accessCode;
    }

    public function setAccessCode(?string $accessCode): static
    {
        $this->accessCode = $accessCode;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setGallery($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getGallery() === $this) {
                $image->setGallery(null);
            }
        }

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

    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    public function requiresAccessCode(): bool
    {
        return $this->visibility === 'private' && !empty($this->accessCode);
    }

    public function getImageCount(): int
    {
        return $this->images->count();
    }

    public function getFirstImage(): ?Image
    {
        return $this->images->first() ?: null;
    }

    public function getCoverImageUrl(): ?string
    {
        if ($this->coverImage) {
            return $this->coverImage;
        }

        $firstImage = $this->getFirstImage();
        return $firstImage ? $firstImage->getUrl() : null;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getMagicLinkToken(): ?string
    {
        if (!$this->isPrivate() || !$this->requiresAccessCode()) {
            return null;
        }

        return hash('sha256', $this->id . ':' . $this->accessCode . ':' . $this->slug);
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->addGallery($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            $event->removeGallery($this);
        }

        return $this;
    }

    public function hasEvents(): bool
    {
        return !$this->events->isEmpty();
    }

    public function getEventCount(): int
    {
        return $this->events->count();
    }

    public function getDurationDays(): ?int
    {
        return $this->durationDays;
    }

    public function setDurationDays(?int $durationDays): static
    {
        $this->durationDays = $durationDays;
        $this->updatedAt = new \DateTimeImmutable();
        
        // Auto-calculate end date if duration is set
        if ($durationDays !== null) {
            $this->endDate = $this->createdAt->modify("+{$durationDays} days");
        }
        
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        $this->updatedAt = new \DateTimeImmutable();
        
        // Auto-calculate duration if end date is set
        if ($endDate !== null && $this->createdAt !== null) {
            $interval = $this->createdAt->diff($endDate);
            $this->durationDays = $interval->days;
        }
        
        return $this;
    }

    public function isExpired(): bool
    {
        if ($this->endDate === null) {
            return false;
        }
        
        return new \DateTimeImmutable() > $this->endDate;
    }

    public function getDaysUntilExpiration(): ?int
    {
        if ($this->endDate === null) {
            return null;
        }
        
        $now = new \DateTimeImmutable();
        if ($now > $this->endDate) {
            return 0; // Already expired
        }
        
        $interval = $now->diff($this->endDate);
        return $interval->days;
    }

    // Security and access methods
    
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isActive(): bool
    {
        return $this->visibility !== 'hidden' && !$this->isExpired();
    }

    public function getOwner(): ?User
    {
        return $this->author;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getPricingType(): string
    {
        return $this->pricingType;
    }

    public function setPricingType(string $pricingType): static
    {
        $this->pricingType = $pricingType;
        return $this;
    }

    public function getAccessPrice(): ?string
    {
        return $this->accessPrice;
    }

    public function setAccessPrice(?string $accessPrice): static
    {
        $this->accessPrice = $accessPrice;
        return $this;
    }

    public function isFree(): bool
    {
        return $this->pricingType === 'free' || empty($this->accessPrice) || $this->accessPrice === '0.00';
    }

    public function isPaid(): bool
    {
        return !$this->isFree();
    }

    public function getFormattedPrice(): string
    {
        if ($this->isFree()) {
            return 'Gratuit';
        }
        return number_format(floatval($this->accessPrice), 2, ',', ' ') . ' €';
    }
}