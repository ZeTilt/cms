<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'certifications')]
class Certification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 255)]
    private string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private string $level = ''; // beginner, intermediate, advanced, expert

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $issuingOrganization = null;

    #[ORM\Column(length: 50)]
    private string $certificationType = ''; // license, certificate, diploma, badge

    #[ORM\Column(nullable: true)]
    private ?int $validityDurationMonths = null; // null = permanent

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $prerequisites = []; // List of required certification IDs

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $competencies = []; // Skills/competencies this certification proves

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null; // For UI display

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null; // Icon class or image path

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    private bool $requiresRenewal = false;

    #[ORM\Column(type: 'boolean')]
    private bool $allowSelfCertification = false; // Users can mark themselves as having this

    #[ORM\Column(nullable: true)]
    private ?int $displayOrder = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'certification', targetEntity: UserCertification::class, orphanRemoval: true)]
    private Collection $userCertifications;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->userCertifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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
        return $this;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getIssuingOrganization(): ?string
    {
        return $this->issuingOrganization;
    }

    public function setIssuingOrganization(?string $issuingOrganization): static
    {
        $this->issuingOrganization = $issuingOrganization;
        return $this;
    }

    public function getCertificationType(): string
    {
        return $this->certificationType;
    }

    public function setCertificationType(string $certificationType): static
    {
        $this->certificationType = $certificationType;
        return $this;
    }

    public function getValidityDurationMonths(): ?int
    {
        return $this->validityDurationMonths;
    }

    public function setValidityDurationMonths(?int $validityDurationMonths): static
    {
        $this->validityDurationMonths = $validityDurationMonths;
        return $this;
    }

    public function getPrerequisites(): ?array
    {
        return $this->prerequisites;
    }

    public function setPrerequisites(?array $prerequisites): static
    {
        $this->prerequisites = $prerequisites;
        return $this;
    }

    public function getCompetencies(): ?array
    {
        return $this->competencies;
    }

    public function setCompetencies(?array $competencies): static
    {
        $this->competencies = $competencies;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function requiresRenewal(): bool
    {
        return $this->requiresRenewal;
    }

    public function setRequiresRenewal(bool $requiresRenewal): static
    {
        $this->requiresRenewal = $requiresRenewal;
        return $this;
    }

    public function allowsSelfCertification(): bool
    {
        return $this->allowSelfCertification;
    }

    public function setAllowSelfCertification(bool $allowSelfCertification): static
    {
        $this->allowSelfCertification = $allowSelfCertification;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, UserCertification>
     */
    public function getUserCertifications(): Collection
    {
        return $this->userCertifications;
    }

    public function addUserCertification(UserCertification $userCertification): static
    {
        if (!$this->userCertifications->contains($userCertification)) {
            $this->userCertifications->add($userCertification);
            $userCertification->setCertification($this);
        }

        return $this;
    }

    public function removeUserCertification(UserCertification $userCertification): static
    {
        if ($this->userCertifications->removeElement($userCertification)) {
            if ($userCertification->getCertification() === $this) {
                $userCertification->setCertification(null);
            }
        }

        return $this;
    }

    // Helper methods

    public function getLevelLabel(): string
    {
        return match($this->level) {
            'beginner' => 'Débutant',
            'intermediate' => 'Intermédiaire',
            'advanced' => 'Avancé',
            'expert' => 'Expert',
            default => $this->level
        };
    }

    public function getLevelColor(): string
    {
        return match($this->level) {
            'beginner' => 'bg-green-100 text-green-800',
            'intermediate' => 'bg-blue-100 text-blue-800',
            'advanced' => 'bg-purple-100 text-purple-800',
            'expert' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getTypeLabel(): string
    {
        return match($this->certificationType) {
            'license' => 'Licence',
            'certificate' => 'Certificat',
            'diploma' => 'Diplôme',
            'badge' => 'Badge',
            default => $this->certificationType
        };
    }

    public function isPermanent(): bool
    {
        return $this->validityDurationMonths === null;
    }

    public function getValidityDescription(): string
    {
        if ($this->isPermanent()) {
            return 'Permanent';
        }
        
        $years = intval($this->validityDurationMonths / 12);
        $months = $this->validityDurationMonths % 12;
        
        $parts = [];
        if ($years > 0) {
            $parts[] = $years . ' an' . ($years > 1 ? 's' : '');
        }
        if ($months > 0) {
            $parts[] = $months . ' mois';
        }
        
        return 'Valide ' . implode(' et ', $parts);
    }

    public function getCertifiedUsersCount(): int
    {
        return $this->userCertifications->filter(
            fn(UserCertification $uc) => $uc->isValid()
        )->count();
    }

    public function hasPrerequisites(): bool
    {
        return !empty($this->prerequisites);
    }

    public function hasCompetencies(): bool
    {
        return !empty($this->competencies);
    }
}