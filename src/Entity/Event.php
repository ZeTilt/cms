<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\ManyToOne(targetEntity: EventType::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EventType $eventType = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $contactPerson = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?bool $isRecurring = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $recurrenceType = null;

    #[ORM\Column(nullable: true)]
    private ?int $recurrenceInterval = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $recurrenceWeekdays = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $recurrenceEndDate = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'generatedEvents')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Event $parentEvent = null;

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'parentEvent', cascade: ['persist', 'remove'])]
    private Collection $generatedEvents;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventCondition::class, cascade: ['persist', 'remove'])]
    private Collection $conditions;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventParticipation::class, cascade: ['persist', 'remove'])]
    private Collection $participations;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $clubMeetingTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $siteMeetingTime = null;

    #[ORM\ManyToOne(targetEntity: DivingLevel::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?DivingLevel $minDivingLevel = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->status = 'active';
        $this->isRecurring = false;
        $this->generatedEvents = new ArrayCollection();
        $this->conditions = new ArrayCollection();
        $this->participations = new ArrayCollection();
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
        return $this;
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
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

    public function getEventType(): ?EventType
    {
        return $this->eventType;
    }

    public function setEventType(?EventType $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->eventType?->getCode();
    }


    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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
        return $this->participations->filter(function(EventParticipation $participation) {
            return $participation->isActive();
        })->count();
    }

    public function getColor(): ?string
    {
        return $this->eventType?->getColor();
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

    public function isFullyBooked(): bool
    {
        return $this->maxParticipants !== null && $this->getCurrentParticipants() >= $this->maxParticipants;
    }

    public function getAvailableSpots(): ?int
    {
        if ($this->maxParticipants === null) {
            return null;
        }
        return max(0, $this->maxParticipants - $this->getCurrentParticipants());
    }

    public function getTypeDisplayName(): string
    {
        return $this->eventType?->getName() ?? 'Inconnu';
    }

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            'active' => 'Actif',
            'cancelled' => 'Annulé',
            'completed' => 'Terminé',
            'draft' => 'Brouillon',
            default => 'Inconnu'
        };
    }

    // Getters et setters pour la récurrence

    public function isRecurring(): ?bool
    {
        return $this->isRecurring;
    }

    public function setRecurring(bool $isRecurring): static
    {
        $this->isRecurring = $isRecurring;
        return $this;
    }

    public function getRecurrenceType(): ?string
    {
        return $this->recurrenceType;
    }

    public function setRecurrenceType(?string $recurrenceType): static
    {
        $this->recurrenceType = $recurrenceType;
        return $this;
    }

    public function getRecurrenceInterval(): ?int
    {
        return $this->recurrenceInterval;
    }

    public function setRecurrenceInterval(?int $recurrenceInterval): static
    {
        $this->recurrenceInterval = $recurrenceInterval;
        return $this;
    }

    public function getRecurrenceWeekdays(): ?array
    {
        return $this->recurrenceWeekdays;
    }

    public function setRecurrenceWeekdays(?array $recurrenceWeekdays): static
    {
        $this->recurrenceWeekdays = $recurrenceWeekdays;
        return $this;
    }

    public function getRecurrenceEndDate(): ?\DateTimeInterface
    {
        return $this->recurrenceEndDate;
    }

    public function setRecurrenceEndDate(?\DateTimeInterface $recurrenceEndDate): static
    {
        $this->recurrenceEndDate = $recurrenceEndDate;
        return $this;
    }

    public function getParentEvent(): ?Event
    {
        return $this->parentEvent;
    }

    public function setParentEvent(?Event $parentEvent): static
    {
        $this->parentEvent = $parentEvent;
        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getGeneratedEvents(): Collection
    {
        return $this->generatedEvents;
    }

    public function addGeneratedEvent(Event $generatedEvent): static
    {
        if (!$this->generatedEvents->contains($generatedEvent)) {
            $this->generatedEvents->add($generatedEvent);
            $generatedEvent->setParentEvent($this);
        }

        return $this;
    }

    public function removeGeneratedEvent(Event $generatedEvent): static
    {
        if ($this->generatedEvents->removeElement($generatedEvent)) {
            if ($generatedEvent->getParentEvent() === $this) {
                $generatedEvent->setParentEvent(null);
            }
        }

        return $this;
    }

    // Méthodes utiles pour la récurrence

    public function getRecurrenceTypeDisplayName(): string
    {
        return match($this->recurrenceType) {
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            'daily' => 'Quotidien',
            default => 'Aucune'
        };
    }

    public function getWeekdaysDisplayNames(): array
    {
        if (!$this->recurrenceWeekdays) {
            return [];
        }

        $weekdayNames = [
            1 => 'Lundi',
            2 => 'Mardi', 
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];

        return array_map(fn($day) => $weekdayNames[$day] ?? $day, $this->recurrenceWeekdays);
    }

    public function isGeneratedEvent(): bool
    {
        return $this->parentEvent !== null;
    }

    /**
     * @return Collection<int, EventCondition>
     */
    public function getConditions(): Collection
    {
        return $this->conditions;
    }

    public function addCondition(EventCondition $condition): static
    {
        if (!$this->conditions->contains($condition)) {
            $this->conditions->add($condition);
            $condition->setEvent($this);
        }

        return $this;
    }

    public function removeCondition(EventCondition $condition): static
    {
        if ($this->conditions->removeElement($condition)) {
            if ($condition->getEvent() === $this) {
                $condition->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * Retourne toutes les conditions actives
     */
    public function getActiveConditions(): Collection
    {
        return $this->conditions->filter(function(EventCondition $condition) {
            return $condition->isActive();
        });
    }

    /**
     * Vérifie l'éligibilité d'un utilisateur selon les conditions définies
     */
    public function checkUserEligibility($user): array
    {
        $issues = [];
        
        foreach ($this->getActiveConditions() as $condition) {
            if (!$condition->checkEntityCondition($user)) {
                $errorMessage = $condition->getErrorMessage() ?: 
                    "Condition non respectée : {$condition->getDisplayName()}";
                $issues[] = $errorMessage;
            }
        }
        
        return $issues;
    }

    public function isUserEligible($user): bool
    {
        return empty($this->checkUserEligibility($user));
    }

    /**
     * Vérifie si l'événement a des conditions d'accès définies
     */
    public function hasRequirements(): bool
    {
        return !$this->getActiveConditions()->isEmpty();
    }

    public function getContactPerson(): ?User
    {
        return $this->contactPerson;
    }

    public function setContactPerson(?User $contactPerson): static
    {
        $this->contactPerson = $contactPerson;
        return $this;
    }

    /**
     * @return Collection<int, EventParticipation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(EventParticipation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setEvent($this);
        }

        return $this;
    }

    public function removeParticipation(EventParticipation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getEvent() === $this) {
                $participation->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * Vérifie si un utilisateur est déjà inscrit à l'événement
     */
    public function isUserRegistered(User $user): bool
    {
        return $this->participations->exists(function($key, EventParticipation $participation) use ($user) {
            return $participation->getParticipant() === $user && $participation->isActive();
        });
    }

    /**
     * Vérifie si l'événement est complet
     */
    public function isFull(): bool
    {
        return $this->maxParticipants !== null && $this->getActiveParticipants() >= $this->maxParticipants;
    }

    /**
     * Retourne le nombre de participants actifs (non en liste d'attente)
     */
    public function getActiveParticipants(): int
    {
        return $this->participations->filter(function(EventParticipation $participation) {
            return $participation->isActive() && !$participation->isWaitingList();
        })->count();
    }

    /**
     * Retourne le nombre de participants en liste d'attente
     */
    public function getWaitingListCount(): int
    {
        return $this->participations->filter(function(EventParticipation $participation) {
            return $participation->isActive() && $participation->isWaitingList();
        })->count();
    }

    /**
     * Retourne les participants actifs (confirmés)
     */
    public function getActiveParticipationsList(): Collection
    {
        return $this->participations->filter(function(EventParticipation $participation) {
            return $participation->isActive() && !$participation->isWaitingList();
        });
    }

    /**
     * Retourne les participants en liste d'attente
     */
    public function getWaitingListParticipations(): Collection
    {
        return $this->participations->filter(function(EventParticipation $participation) {
            return $participation->isActive() && $participation->isWaitingList();
        });
    }

    /**
     * Retourne les participants au RDV club
     */
    public function getClubMeetingParticipants(): Collection
    {
        return $this->participations->filter(function(EventParticipation $participation) {
            return $participation->isActive() && !$participation->isWaitingList() && $participation->getMeetingPoint() === 'club';
        });
    }

    /**
     * Retourne les participants au RDV sur site
     */
    public function getSiteMeetingParticipants(): Collection
    {
        return $this->participations->filter(function(EventParticipation $participation) {
            return $participation->isActive() && !$participation->isWaitingList() && $participation->getMeetingPoint() === 'site';
        });
    }

    /**
     * Retourne le contact effectif de l'événement
     * - Si une personne contact est définie, on l'utilise
     * - Sinon on cherche le directeur de plongée si c'est une activité de plongée
     * - Sinon on utilise les coordonnées du club (via la config)
     */
    public function getEffectiveContact(): ?User
    {
        if ($this->contactPerson) {
            return $this->contactPerson;
        }

        // Si c'est une plongée et qu'il n'y a pas de contact défini,
        // on devrait retourner le directeur de plongée
        // TODO: implémenter la logique pour récupérer le directeur de plongée
        
        return null;
    }

    public function getClubMeetingTime(): ?\DateTimeInterface
    {
        return $this->clubMeetingTime;
    }

    public function setClubMeetingTime(?\DateTimeInterface $clubMeetingTime): static
    {
        $this->clubMeetingTime = $clubMeetingTime;
        return $this;
    }

    public function getSiteMeetingTime(): ?\DateTimeInterface
    {
        return $this->siteMeetingTime;
    }

    public function setSiteMeetingTime(?\DateTimeInterface $siteMeetingTime): static
    {
        $this->siteMeetingTime = $siteMeetingTime;
        return $this;
    }

    public function getMinDivingLevel(): ?DivingLevel
    {
        return $this->minDivingLevel;
    }

    public function setMinDivingLevel(?DivingLevel $minDivingLevel): static
    {
        $this->minDivingLevel = $minDivingLevel;
        return $this;
    }

    /**
     * Vérifie si un utilisateur peut s'inscrire (niveau suffisant)
     */
    public function canUserRegister(User $user): bool
    {
        // Si pas de niveau minimum requis, tout le monde peut s'inscrire
        if (!$this->minDivingLevel) {
            return true;
        }

        // TODO: Récupérer le niveau de l'utilisateur depuis les EntityAttributes
        // et comparer avec le niveau minimum requis
        return true; // Temporaire
    }

    public function __toString(): string
    {
        return $this->title ?? 'Événement #' . ($this->id ?? 'nouveau');
    }
}