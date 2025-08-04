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

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $clubDepartureTime = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dockDepartureTime = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $pilot = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $divingComments = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $registrationConditions = null; // Conditions d'inscription basées sur les attributs EAV

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventAttribute::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $eventAttributes;

    #[ORM\ManyToMany(targetEntity: Gallery::class, inversedBy: 'events')]
    #[ORM\JoinTable(name: 'event_galleries')]
    private Collection $galleries;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventRegistration::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $registrations;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->eventAttributes = new ArrayCollection();
        $this->registrations = new ArrayCollection();
        $this->galleries = new ArrayCollection();
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
        $totalSpots = 0;
        foreach ($this->getActiveRegistrations() as $registration) {
            $totalSpots += $registration->getNumberOfSpots();
        }
        return $totalSpots;
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

        return max(0, $this->maxParticipants - $this->getConfirmedParticipants());
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
        $totalSpots = 0;
        foreach ($this->getConfirmedRegistrations() as $registration) {
            $totalSpots += $registration->getNumberOfSpots();
        }
        return $totalSpots;
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

    public function getClubDepartureTime(): ?\DateTimeImmutable
    {
        return $this->clubDepartureTime;
    }

    public function setClubDepartureTime(?\DateTimeImmutable $clubDepartureTime): static
    {
        $this->clubDepartureTime = $clubDepartureTime;
        return $this;
    }

    public function getDockDepartureTime(): ?\DateTimeImmutable
    {
        return $this->dockDepartureTime;
    }

    public function setDockDepartureTime(?\DateTimeImmutable $dockDepartureTime): static
    {
        $this->dockDepartureTime = $dockDepartureTime;
        return $this;
    }

    public function getPilot(): ?User
    {
        return $this->pilot;
    }

    public function setPilot(?User $pilot): static
    {
        $this->pilot = $pilot;
        return $this;
    }

    public function getDivingComments(): ?string
    {
        return $this->divingComments;
    }

    public function setDivingComments(?string $divingComments): static
    {
        $this->divingComments = $divingComments;
        return $this;
    }

    public function getRegistrationConditions(): ?array
    {
        return $this->registrationConditions;
    }

    public function setRegistrationConditions(?array $registrationConditions): static
    {
        $this->registrationConditions = $registrationConditions;
        return $this;
    }

    /**
     * Ajouter une condition d'inscription
     */
    public function addRegistrationCondition(string $attributeKey, string $operator, mixed $value, string $message = ''): static
    {
        if (!$this->registrationConditions) {
            $this->registrationConditions = [];
        }

        $this->registrationConditions[] = [
            'attribute_key' => $attributeKey,
            'operator' => $operator, // equals, not_equals, greater_than, less_than, contains, exists, not_exists
            'value' => $value,
            'message' => $message ?: "Condition non respectée pour l'attribut {$attributeKey}"
        ];

        return $this;
    }

    /**
     * Supprimer toutes les conditions d'inscription
     */
    public function clearRegistrationConditions(): static
    {
        $this->registrationConditions = [];
        return $this;
    }

    /**
     * Vérifier si un utilisateur peut s'inscrire à cet événement
     */
    public function canUserRegister(User $user): array
    {
        $errors = [];

        if (!$this->registrationConditions) {
            return $errors;
        }

        foreach ($this->registrationConditions as $condition) {
            $attributeKey = $condition['attribute_key'];
            $operator = $condition['operator'];
            $expectedValue = $condition['value'];
            $message = $condition['message'];

            $userValue = $user->getDynamicAttribute($attributeKey);

            $conditionMet = $this->evaluateCondition($userValue, $operator, $expectedValue, $attributeKey);

            if (!$conditionMet) {
                $errors[] = $message;
            }
        }

        return $errors;
    }

    /**
     * Évaluer une condition spécifique
     */
    private function evaluateCondition(mixed $userValue, string $operator, mixed $expectedValue, string $attributeKey = null): bool
    {
        return match($operator) {
            'equals' => $userValue == $expectedValue,
            'not_equals' => $userValue != $expectedValue,
            'greater_than' => is_numeric($userValue) && is_numeric($expectedValue) && $userValue > $expectedValue,
            'less_than' => is_numeric($userValue) && is_numeric($expectedValue) && $userValue < $expectedValue,
            'greater_or_equal' => is_numeric($userValue) && is_numeric($expectedValue) && $userValue >= $expectedValue,
            'less_or_equal' => is_numeric($userValue) && is_numeric($expectedValue) && $userValue <= $expectedValue,
            'select_option_gte' => $this->compareSelectOptions($userValue, $expectedValue, $attributeKey) >= 0,
            'select_option_equals' => $this->compareSelectOptions($userValue, $expectedValue, $attributeKey) === 0,
            'contains' => is_string($userValue) && is_string($expectedValue) && str_contains($userValue, $expectedValue),
            'exists' => $userValue !== null && $userValue !== '',
            'not_exists' => $userValue === null || $userValue === '',
            'in_list' => is_array($expectedValue) && in_array($userValue, $expectedValue),
            'not_in_list' => is_array($expectedValue) && !in_array($userValue, $expectedValue),
            default => false
        };
    }

    /**
     * Comparer les options d'un attribut select basé sur leur ordre dans la définition EAV
     * Retourne : > 0 si userValue > requiredValue, 0 si égaux, < 0 si userValue < requiredValue
     */
    private function compareSelectOptions(?string $userValue, ?string $requiredValue, ?string $attributeKey): int
    {
        if (!$userValue || !$requiredValue || !$attributeKey) {
            return -1;
        }
        
        // Récupérer les options de l'attribut depuis la définition EAV
        $options = $this->getAttributeOptions($attributeKey);
        
        if (!$options) {
            return -1;
        }
        
        $userValueIndex = array_search($userValue, $options);
        $requiredValueIndex = array_search($requiredValue, $options);
        
        // Si l'une des valeurs n'est pas trouvée dans les options, on refuse
        if ($userValueIndex === false || $requiredValueIndex === false) {
            return -1;
        }
        
        // Plus l'index est élevé dans la liste, plus la valeur est élevée hiérarchiquement
        return $userValueIndex - $requiredValueIndex;
    }
    
    /**
     * Récupérer les options d'un attribut depuis la définition EAV
     */
    private function getAttributeOptions(string $attributeName): ?array
    {
        // Cache statique pour éviter les requêtes multiples dans une même requête
        static $optionsCache = [];
        
        if (!isset($optionsCache[$attributeName])) {
            // Simuler l'injection de dépendance pour accéder à l'EntityManager
            // En pratique, il faudrait injecter l'EntityManager ou un service
            
            // Pour l'instant, récupérer directement depuis la base
            // Note: Cette approche n'est pas idéale car l'entité fait un appel DB
            
            try {
                // Récupérer depuis la table attribute_definitions
                // Chemin direct vers la base SQLite du projet
                $dbPath = __DIR__ . '/../../var/data_dev.db';
                $pdo = new \PDO('sqlite:' . $dbPath);
                $stmt = $pdo->prepare('SELECT options FROM attribute_definitions WHERE attribute_name = ?');
                $stmt->execute([$attributeName]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($result && $result['options']) {
                    $optionsCache[$attributeName] = json_decode($result['options'], true);
                } else {
                    $optionsCache[$attributeName] = null;
                }
            } catch (\Exception $e) {
                $optionsCache[$attributeName] = null;
            }
        }
        
        return $optionsCache[$attributeName];
    }

    /**
     * Obtenir la liste des exigences pour cet événement
     */
    public function getRequirements(): array
    {
        $requirements = [];

        if (!$this->registrationConditions) {
            return $requirements;
        }

        foreach ($this->registrationConditions as $condition) {
            $requirements[] = $this->formatConditionAsRequirement($condition);
        }

        return $requirements;
    }

    /**
     * Formater une condition en exigence lisible
     */
    private function formatConditionAsRequirement(array $condition): string
    {
        $attributeKey = $condition['attribute_key'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        return match($operator) {
            'equals' => "{$attributeKey}: {$value}",
            'not_equals' => "{$attributeKey}: différent de {$value}",
            'greater_than' => "{$attributeKey}: supérieur à {$value}",
            'less_than' => "{$attributeKey}: inférieur à {$value}",
            'greater_or_equal' => "{$attributeKey}: {$value} minimum",
            'less_or_equal' => "{$attributeKey}: {$value} maximum",
            'contains' => "{$attributeKey}: doit contenir '{$value}'",
            'exists' => "{$attributeKey}: requis",
            'not_exists' => "{$attributeKey}: non autorisé",
            'in_list' => "{$attributeKey}: " . (is_array($value) ? implode(', ', $value) : $value),
            'not_in_list' => "{$attributeKey}: interdit: " . (is_array($value) ? implode(', ', $value) : $value),
            default => $condition['message'] ?? "Condition sur {$attributeKey}"
        };
    }

    /**
     * @return Collection<int, Gallery>
     */
    public function getGalleries(): Collection
    {
        return $this->galleries;
    }

    public function addGallery(Gallery $gallery): static
    {
        if (!$this->galleries->contains($gallery)) {
            $this->galleries->add($gallery);
        }

        return $this;
    }

    public function removeGallery(Gallery $gallery): static
    {
        $this->galleries->removeElement($gallery);

        return $this;
    }

    public function hasGalleries(): bool
    {
        return !$this->galleries->isEmpty();
    }

    public function getGalleryCount(): int
    {
        return $this->galleries->count();
    }
}