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
    #[ORM\JoinColumn(nullable: true)]
    private ?EventType $eventType = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\Column(nullable: true)]
    private ?int $currentParticipants = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $color = null;

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

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $minDivingLevel = null;

    #[ORM\Column(nullable: true)]
    private ?int $minAge = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxAge = null;

    #[ORM\Column]
    private ?bool $requiresMedicalCertificate = false;

    #[ORM\Column(nullable: true)]
    private ?int $medicalCertificateValidityDays = null;

    #[ORM\Column]
    private ?bool $requiresSwimmingTest = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalRequirements = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->status = 'active';
        $this->currentParticipants = 0;
        $this->isRecurring = false;
        $this->generatedEvents = new ArrayCollection();
        $this->requiresMedicalCertificate = false;
        $this->requiresSwimmingTest = false;
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
        // Retourner le code du type d'événement si disponible, sinon l'ancien champ
        return $this->eventType?->getCode() ?? $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
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

    public function getCurrentParticipants(): ?int
    {
        return $this->currentParticipants;
    }

    public function setCurrentParticipants(?int $currentParticipants): static
    {
        $this->currentParticipants = $currentParticipants;
        return $this;
    }

    public function getColor(): ?string
    {
        // Retourner la couleur du type d'événement si disponible, sinon l'ancien champ
        return $this->eventType?->getColor() ?? $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;
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

    public function isFullyBooked(): bool
    {
        return $this->maxParticipants !== null && $this->currentParticipants >= $this->maxParticipants;
    }

    public function getAvailableSpots(): ?int
    {
        if ($this->maxParticipants === null) {
            return null;
        }
        return max(0, $this->maxParticipants - $this->currentParticipants);
    }

    public function getTypeDisplayName(): string
    {
        // Retourner le nom du type d'événement si disponible, sinon mapper l'ancien champ
        if ($this->eventType) {
            return $this->eventType->getName();
        }
        
        return match($this->type) {
            'training' => 'Formation',
            'dive' => 'Plongée',
            'trip' => 'Sortie',
            'meeting' => 'Réunion',
            'maintenance' => 'Maintenance',
            'event' => 'Événement',
            default => 'Activité'
        };
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

    public function getMinDivingLevel(): ?string
    {
        return $this->minDivingLevel;
    }

    public function setMinDivingLevel(?string $minDivingLevel): static
    {
        $this->minDivingLevel = $minDivingLevel;
        return $this;
    }

    public function getMinAge(): ?int
    {
        return $this->minAge;
    }

    public function setMinAge(?int $minAge): static
    {
        $this->minAge = $minAge;
        return $this;
    }

    public function getMaxAge(): ?int
    {
        return $this->maxAge;
    }

    public function setMaxAge(?int $maxAge): static
    {
        $this->maxAge = $maxAge;
        return $this;
    }

    public function requiresMedicalCertificate(): ?bool
    {
        return $this->requiresMedicalCertificate;
    }

    public function setRequiresMedicalCertificate(bool $requiresMedicalCertificate): static
    {
        $this->requiresMedicalCertificate = $requiresMedicalCertificate;
        return $this;
    }

    public function getMedicalCertificateValidityDays(): ?int
    {
        return $this->medicalCertificateValidityDays;
    }

    public function setMedicalCertificateValidityDays(?int $medicalCertificateValidityDays): static
    {
        $this->medicalCertificateValidityDays = $medicalCertificateValidityDays;
        return $this;
    }

    public function requiresSwimmingTest(): ?bool
    {
        return $this->requiresSwimmingTest;
    }

    public function setRequiresSwimmingTest(bool $requiresSwimmingTest): static
    {
        $this->requiresSwimmingTest = $requiresSwimmingTest;
        return $this;
    }

    public function getAdditionalRequirements(): ?string
    {
        return $this->additionalRequirements;
    }

    public function setAdditionalRequirements(?string $additionalRequirements): static
    {
        $this->additionalRequirements = $additionalRequirements;
        return $this;
    }

    public function checkUserEligibility($user): array
    {
        $issues = [];
        
        // Check diving level if user has method available
        if ($this->minDivingLevel && method_exists($user, 'getDivingLevel')) {
            $userDivingLevel = $user->getDivingLevel();
            if ($userDivingLevel) {
                $divingLevels = [
                    'N1' => 1, 'N2' => 2, 'N3' => 3, 'N4' => 4, 'N5' => 5,
                    'P1' => 1, 'P2' => 2, 'P3' => 3, 'P4' => 4, 'P5' => 5,
                    'E1' => 6, 'E2' => 7, 'E3' => 8, 'E4' => 9,
                    'MF1' => 10, 'MF2' => 11,
                    'RIFAP' => 12, 'RIFAP_recyclage' => 12
                ];
                
                $userLevel = $divingLevels[$userDivingLevel] ?? 0;
                $requiredLevel = $divingLevels[$this->minDivingLevel] ?? 0;
                
                if ($userLevel < $requiredLevel) {
                    $issues[] = "Niveau de plongée requis : {$this->minDivingLevel} (vous avez : {$userDivingLevel})";
                }
            } else {
                $issues[] = "Niveau de plongée requis : {$this->minDivingLevel} (niveau non renseigné)";
            }
        } elseif ($this->minDivingLevel) {
            $issues[] = "Niveau de plongée requis : {$this->minDivingLevel} (niveau non disponible dans votre profil)";
        }
        
        // Check age if user has birth date method available
        if (($this->minAge !== null || $this->maxAge !== null) && method_exists($user, 'getBirthDate')) {
            $birthDate = $user->getBirthDate();
            if ($birthDate) {
                $age = $birthDate->diff(new \DateTime())->y;
                if ($this->minAge !== null && $age < $this->minAge) {
                    $issues[] = "Âge minimum requis : {$this->minAge} ans (vous avez : {$age} ans)";
                }
                if ($this->maxAge !== null && $age > $this->maxAge) {
                    $issues[] = "Âge maximum autorisé : {$this->maxAge} ans (vous avez : {$age} ans)";
                }
            } else {
                if ($this->minAge !== null) {
                    $issues[] = "Âge minimum requis : {$this->minAge} ans (date de naissance non renseignée)";
                }
                if ($this->maxAge !== null) {
                    $issues[] = "Âge maximum autorisé : {$this->maxAge} ans (date de naissance non renseignée)";
                }
            }
        } elseif ($this->minAge !== null || $this->maxAge !== null) {
            if ($this->minAge !== null) {
                $issues[] = "Âge minimum requis : {$this->minAge} ans (date de naissance non disponible dans votre profil)";
            }
            if ($this->maxAge !== null) {
                $issues[] = "Âge maximum autorisé : {$this->maxAge} ans (date de naissance non disponible dans votre profil)";
            }
        }
        
        // Check medical certificate
        if ($this->requiresMedicalCertificate) {
            if (method_exists($user, 'getMedicalCertificateDate')) {
                $certDate = $user->getMedicalCertificateDate();
                if ($certDate) {
                    $validityDays = $this->medicalCertificateValidityDays ?? 365;
                    $expiryDate = clone $certDate;
                    $expiryDate->add(new \DateInterval("P{$validityDays}D"));
                    if ($expiryDate < new \DateTime()) {
                        $issues[] = "Certificat médical expiré (valide jusqu'au : " . $expiryDate->format('d/m/Y') . ")";
                    }
                } else {
                    $issues[] = "Certificat médical requis (non renseigné)";
                }
            } else {
                $issues[] = "Certificat médical requis (information non disponible dans votre profil)";
            }
        }
        
        // Check swimming test
        if ($this->requiresSwimmingTest) {
            if (method_exists($user, 'hasValidSwimmingTest')) {
                if (!$user->hasValidSwimmingTest()) {
                    $issues[] = "Test de natation requis";
                }
            } else {
                $issues[] = "Test de natation requis (information non disponible dans votre profil)";
            }
        }
        
        return $issues;
    }

    public function isUserEligible($user): bool
    {
        return empty($this->checkUserEligibility($user));
    }

    public function hasRequirements(): bool
    {
        return !empty($this->minDivingLevel) 
            || $this->minAge !== null 
            || $this->maxAge !== null 
            || $this->requiresMedicalCertificate 
            || $this->requiresSwimmingTest 
            || !empty($this->additionalRequirements);
    }

    public function __toString(): string
    {
        return $this->title ?? 'Événement #' . ($this->id ?? 'nouveau');
    }
}