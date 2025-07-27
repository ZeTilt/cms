<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'events')]
class Event
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
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 20)]
    private string $status = 'draft'; // draft, published, cancelled

    #[ORM\Column(length: 50)]
    private string $type = 'event'; // event, meeting, conference, workshop

    #[ORM\Column(type: 'json')]
    private array $tags = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $featuredImage = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metaData = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\Column]
    private int $currentParticipants = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $requiresRegistration = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isRecurring = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $recurringConfig = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $organizer;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventAttribute::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $eventAttributes;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventRegistration::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $registrations;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->eventAttributes = new ArrayCollection();
        $this->registrations = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        $this->updatedAt = new \DateTimeImmutable();
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
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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

    public function getFeaturedImage(): ?string
    {
        return $this->featuredImage;
    }

    public function setFeaturedImage(?string $featuredImage): static
    {
        $this->featuredImage = $featuredImage;
        return $this;
    }

    public function getMetaData(): ?array
    {
        return $this->metaData;
    }

    public function setMetaData(?array $metaData): static
    {
        $this->metaData = $metaData;
        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;
        return $this;
    }

    public function getCurrentParticipants(): int
    {
        return $this->currentParticipants;
    }

    public function setCurrentParticipants(int $currentParticipants): static
    {
        $this->currentParticipants = $currentParticipants;
        return $this;
    }

    public function isRequiresRegistration(): bool
    {
        return $this->requiresRegistration;
    }

    public function setRequiresRegistration(bool $requiresRegistration): static
    {
        $this->requiresRegistration = $requiresRegistration;
        return $this;
    }

    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

    public function setIsRecurring(bool $isRecurring): static
    {
        $this->isRecurring = $isRecurring;
        return $this;
    }

    public function getRecurringConfig(): ?array
    {
        return $this->recurringConfig;
    }

    public function setRecurringConfig(?array $recurringConfig): static
    {
        $this->recurringConfig = $recurringConfig;
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

    public function getOrganizer(): User
    {
        return $this->organizer;
    }

    public function setOrganizer(User $organizer): static
    {
        $this->organizer = $organizer;
        return $this;
    }

    /**
     * Check if the event is currently active (ongoing)
     */
    public function isActive(): bool
    {
        if (!$this->startDate || !$this->endDate) {
            return false;
        }

        $now = new \DateTimeImmutable();
        return $this->startDate <= $now && $now <= $this->endDate;
    }

    /**
     * Check if the event is upcoming
     */
    public function isUpcoming(): bool
    {
        if (!$this->startDate) {
            return false;
        }

        return $this->startDate > new \DateTimeImmutable();
    }

    /**
     * Check if the event is past
     */
    public function isPast(): bool
    {
        if (!$this->endDate) {
            return false;
        }

        return $this->endDate < new \DateTimeImmutable();
    }

    /**
     * Check if the event is full (for registration)
     */
    public function isFull(): bool
    {
        if (!$this->maxParticipants) {
            return false;
        }

        return $this->currentParticipants >= $this->maxParticipants;
    }

    /**
     * Get available spots for registration
     */
    public function getAvailableSpots(): ?int
    {
        if (!$this->maxParticipants) {
            return null;
        }

        return max(0, $this->maxParticipants - $this->currentParticipants);
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes(): ?int
    {
        if (!$this->startDate || !$this->endDate) {
            return null;
        }

        return $this->endDate->getTimestamp() - $this->startDate->getTimestamp();
    }

    /**
     * Get formatted duration (e.g., "2h 30m")
     */
    public function getFormattedDuration(): ?string
    {
        $minutes = $this->getDurationMinutes();
        if (!$minutes) {
            return null;
        }

        $hours = intval($minutes / 3600);
        $mins = intval(($minutes % 3600) / 60);

        if ($hours > 0 && $mins > 0) {
            return sprintf('%dh %dm', $hours, $mins);
        } elseif ($hours > 0) {
            return sprintf('%dh', $hours);
        } else {
            return sprintf('%dm', $mins);
        }
    }

    /**
     * @return Collection<int, EventAttribute>
     */
    public function getEventAttributes(): Collection
    {
        return $this->eventAttributes;
    }

    public function addEventAttribute(EventAttribute $eventAttribute): static
    {
        if (!$this->eventAttributes->contains($eventAttribute)) {
            $this->eventAttributes->add($eventAttribute);
            $eventAttribute->setEvent($this);
        }

        return $this;
    }

    public function removeEventAttribute(EventAttribute $eventAttribute): static
    {
        if ($this->eventAttributes->removeElement($eventAttribute)) {
            if ($eventAttribute->getEvent() === $this) {
                $eventAttribute->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * Get event attribute by key
     */
    public function getEventAttributeByKey(string $key): ?EventAttribute
    {
        foreach ($this->eventAttributes as $attribute) {
            if ($attribute->getAttributeKey() === $key) {
                return $attribute;
            }
        }
        return null;
    }

    /**
     * Get attribute value by key
     */
    public function getAttributeValue(string $key): mixed
    {
        $attribute = $this->getEventAttributeByKey($key);
        return $attribute ? $attribute->getTypedValue() : null;
    }

    /**
     * Set attribute value by key
     */
    public function setAttributeValue(string $key, mixed $value, string $type = 'text'): static
    {
        $attribute = $this->getEventAttributeByKey($key);
        
        if (!$attribute) {
            $attribute = new EventAttribute();
            $attribute->setEvent($this);
            $attribute->setAttributeKey($key);
            $attribute->setAttributeType($type);
            $this->addEventAttribute($attribute);
        }
        
        $attribute->setTypedValue($value);
        return $this;
    }

    /**
     * @return Collection<int, EventRegistration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(EventRegistration $registration): static
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setEvent($this);
        }

        return $this;
    }

    public function removeRegistration(EventRegistration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            if ($registration->getEvent() === $this) {
                $registration->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * Get active registrations (registered + waiting list)
     */
    public function getActiveRegistrations(): Collection
    {
        return $this->registrations->filter(fn(EventRegistration $reg) => $reg->isActive());
    }

    /**
     * Get confirmed registrations (not on waiting list)
     */
    public function getConfirmedRegistrations(): Collection
    {
        return $this->registrations->filter(fn(EventRegistration $reg) => $reg->getStatus() === 'registered');
    }

    /**
     * Get waiting list registrations
     */
    public function getWaitingListRegistrations(): Collection
    {
        return $this->registrations->filter(fn(EventRegistration $reg) => $reg->getStatus() === 'waiting_list');
    }

    /**
     * Check if user is registered for this event
     */
    public function isUserRegistered(User $user): bool
    {
        foreach ($this->getActiveRegistrations() as $registration) {
            if ($registration->getUser() === $user) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get user's registration for this event
     */
    public function getUserRegistration(User $user): ?EventRegistration
    {
        foreach ($this->getActiveRegistrations() as $registration) {
            if ($registration->getUser() === $user) {
                return $registration;
            }
        }
        return null;
    }

    /**
     * Get current number of confirmed participants
     */
    public function getConfirmedParticipants(): int
    {
        return $this->getConfirmedRegistrations()->count();
    }

    /**
     * Get current number of waiting list participants
     */
    public function getWaitingListCount(): int
    {
        return $this->getWaitingListRegistrations()->count();
    }

    /**
     * Check if event accepts new registrations
     */
    public function acceptsRegistrations(): bool
    {
        return $this->requiresRegistration && 
               $this->status === 'published' && 
               $this->isUpcoming();
    }

    /**
     * Check if event is full and new registrations go to waiting list
     */
    public function requiresWaitingList(): bool
    {
        return $this->maxParticipants && 
               $this->getConfirmedParticipants() >= $this->maxParticipants;
    }
}