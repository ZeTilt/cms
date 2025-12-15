<?php

namespace App\Entity;

use App\Repository\MedicalCertificateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedicalCertificateRepository::class)]
#[ORM\Table(name: 'medical_certificates')]
#[ORM\HasLifecycleCallbacks]
class MedicalCertificate
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $encryptedFilePath;

    #[ORM\Column(length: 255)]
    private string $originalFilename;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $expiryDate;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'boolean')]
    private bool $consentGiven = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $uploadedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $validatedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $scheduledDeletionDate = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->uploadedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getEncryptedFilePath(): string
    {
        return $this->encryptedFilePath;
    }

    public function setEncryptedFilePath(string $encryptedFilePath): static
    {
        $this->encryptedFilePath = $encryptedFilePath;
        return $this;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;
        return $this;
    }

    public function getExpiryDate(): \DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(\DateTimeInterface $expiryDate): static
    {
        $this->expiryDate = $expiryDate;
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

    public function isConsentGiven(): bool
    {
        return $this->consentGiven;
    }

    public function setConsentGiven(bool $consentGiven): static
    {
        $this->consentGiven = $consentGiven;
        return $this;
    }

    public function getUploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;
        return $this;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?User $validatedBy): static
    {
        $this->validatedBy = $validatedBy;
        return $this;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getScheduledDeletionDate(): ?\DateTimeInterface
    {
        return $this->scheduledDeletionDate;
    }

    public function setScheduledDeletionDate(?\DateTimeInterface $scheduledDeletionDate): static
    {
        $this->scheduledDeletionDate = $scheduledDeletionDate;
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

    // === Business logic methods ===

    /**
     * Validate the certificate (called by CACI Referent)
     */
    public function validate(User $validator): static
    {
        $this->status = self::STATUS_VALIDATED;
        $this->validatedBy = $validator;
        $this->validatedAt = new \DateTimeImmutable();
        $this->rejectionReason = null;

        // Schedule deletion 1 year after expiry
        $deletionDate = \DateTime::createFromInterface($this->expiryDate);
        $deletionDate->modify('+1 year');
        $this->scheduledDeletionDate = $deletionDate;

        return $this;
    }

    /**
     * Reject the certificate (called by CACI Referent)
     */
    public function reject(User $validator, string $reason): static
    {
        $this->status = self::STATUS_REJECTED;
        $this->validatedBy = $validator;
        $this->validatedAt = new \DateTimeImmutable();
        $this->rejectionReason = $reason;

        // Schedule deletion in 30 days for rejected certificates
        $deletionDate = new \DateTime();
        $deletionDate->modify('+30 days');
        $this->scheduledDeletionDate = $deletionDate;

        return $this;
    }

    /**
     * Is the certificate pending validation?
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Is the certificate validated?
     */
    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    /**
     * Is the certificate rejected?
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Is the certificate expired?
     */
    public function isExpired(): bool
    {
        return $this->expiryDate < new \DateTime('today');
    }

    /**
     * Is the certificate currently valid? (validated AND not expired)
     */
    public function isValid(): bool
    {
        return $this->isValidated() && !$this->isExpired();
    }

    /**
     * Can the user register to events with this certificate?
     * (pending OR validated) AND not expired
     */
    public function allowsEventRegistration(): bool
    {
        return !$this->isRejected() && !$this->isExpired();
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente de validation',
            self::STATUS_VALIDATED => 'Validé',
            self::STATUS_REJECTED => 'Rejeté',
            default => 'Inconnu'
        };
    }

    /**
     * Get CSS class for status badge
     */
    public function getStatusBadgeClass(): string
    {
        if ($this->isExpired()) {
            return 'badge-error';
        }

        return match($this->status) {
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_VALIDATED => 'badge-success',
            self::STATUS_REJECTED => 'badge-error',
            default => 'badge-neutral'
        };
    }
}
