<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_certifications')]
class UserCertification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Certification::class, inversedBy: 'userCertifications')]
    #[ORM\JoinColumn(nullable: false)]
    private Certification $certification;

    #[ORM\Column]
    private \DateTimeImmutable $obtainedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(length: 50)]
    private string $status = 'active'; // active, expired, suspended, revoked

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $certificateNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $issuingAuthority = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->obtainedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCertification(): Certification
    {
        return $this->certification;
    }

    public function setCertification(Certification $certification): static
    {
        $this->certification = $certification;
        return $this;
    }

    public function getObtainedAt(): \DateTimeImmutable
    {
        return $this->obtainedAt;
    }

    public function setObtainedAt(\DateTimeImmutable $obtainedAt): static
    {
        $this->obtainedAt = $obtainedAt;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getCertificateNumber(): ?string
    {
        return $this->certificateNumber;
    }

    public function setCertificateNumber(?string $certificateNumber): static
    {
        $this->certificateNumber = $certificateNumber;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getIssuingAuthority(): ?string
    {
        return $this->issuingAuthority;
    }

    public function setIssuingAuthority(?string $issuingAuthority): static
    {
        $this->issuingAuthority = $issuingAuthority;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable();
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

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // Helper methods

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        if (!$this->expiresAt) {
            return false; // No expiration date means it never expires
        }
        
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiresAt) {
            return false;
        }
        
        $warningDate = new \DateTimeImmutable("+{$days} days");
        return $this->expiresAt <= $warningDate && !$this->isExpired();
    }

    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->expiresAt) {
            return null;
        }
        
        $now = new \DateTimeImmutable();
        if ($this->expiresAt < $now) {
            return 0; // Already expired
        }
        
        return $now->diff($this->expiresAt)->days;
    }

    public function getStatusLabel(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }
        
        return match($this->status) {
            'active' => 'Active',
            'expired' => 'Expired',
            'suspended' => 'Suspended',
            'revoked' => 'Revoked',
            default => $this->status
        };
    }

    public function getStatusColor(): string
    {
        if ($this->isExpired()) {
            return 'bg-red-100 text-red-800';
        }
        
        if ($this->isExpiringSoon()) {
            return 'bg-orange-100 text-orange-800';
        }
        
        return match($this->status) {
            'active' => 'bg-green-100 text-green-800',
            'expired' => 'bg-red-100 text-red-800',
            'suspended' => 'bg-yellow-100 text-yellow-800',
            'revoked' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getValidityStatus(): string
    {
        if ($this->status !== 'active') {
            return $this->getStatusLabel();
        }
        
        if ($this->isExpired()) {
            return 'Expired';
        }
        
        if ($this->isExpiringSoon()) {
            $days = $this->getDaysUntilExpiration();
            return "Expires in {$days} day" . ($days > 1 ? 's' : '');
        }
        
        return 'Valid';
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s (%s)', 
            $this->user->getUsername(),
            $this->certification->getName(),
            $this->getStatusLabel()
        );
    }
}