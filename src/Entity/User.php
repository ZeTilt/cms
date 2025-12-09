<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: 'boolean')]
    private bool $emailVerified = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetPasswordTokenExpiry = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: DivingLevel::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?DivingLevel $highestDivingLevel = null;

    #[ORM\ManyToOne(targetEntity: FreedivingLevel::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?FreedivingLevel $highestFreedivingLevel = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isDiver = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isFreediver = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isPilot = false;

    // Nouveaux champs pour remplacer le système EAV
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $licenceNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $licenceFile = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $licenceExpiry = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $medicalCertificateDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $medicalCertificateExpiry = null;

    // Vérification CACI par le DP
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $medicalCertificateVerifiedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $medicalCertificateVerifiedBy = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $insuranceNumber = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $insuranceExpiry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emergencyContactName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $emergencyContactPhone = null;

    // Informations personnelles (visibles uniquement par les DP)
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarFile = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Gallery::class, orphanRemoval: true)]
    private Collection $galleries;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->galleries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email ?? '';
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
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
            $gallery->setAuthor($this);
        }

        return $this;
    }

    public function removeGallery(Gallery $gallery): static
    {
        if ($this->galleries->removeElement($gallery)) {
            if ($gallery->getAuthor() === $this) {
                $gallery->setAuthor(null);
            }
        }

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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getHighestDivingLevel(): ?DivingLevel
    {
        return $this->highestDivingLevel;
    }

    public function setHighestDivingLevel(?DivingLevel $highestDivingLevel): static
    {
        $this->highestDivingLevel = $highestDivingLevel;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isInstructor(): bool
    {
        return $this->highestDivingLevel !== null && $this->highestDivingLevel->isInstructor();
    }

    public function getHighestFreedivingLevel(): ?FreedivingLevel
    {
        return $this->highestFreedivingLevel;
    }

    public function setHighestFreedivingLevel(?FreedivingLevel $highestFreedivingLevel): static
    {
        $this->highestFreedivingLevel = $highestFreedivingLevel;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isFreedivingInstructor(): bool
    {
        return $this->highestFreedivingLevel !== null && $this->highestFreedivingLevel->isInstructor();
    }

    public function isDiver(): bool
    {
        return $this->isDiver;
    }

    public function setDiver(bool $isDiver): static
    {
        $this->isDiver = $isDiver;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isFreediver(): bool
    {
        return $this->isFreediver;
    }

    public function setFreediver(bool $isFreediver): static
    {
        $this->isFreediver = $isFreediver;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isPilot(): bool
    {
        return $this->isPilot;
    }

    public function setPilot(bool $isPilot): static
    {
        $this->isPilot = $isPilot;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): static
    {
        $this->emailVerified = $emailVerified;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $emailVerificationToken): static
    {
        $this->emailVerificationToken = $emailVerificationToken;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function setResetPasswordToken(?string $resetPasswordToken): static
    {
        $this->resetPasswordToken = $resetPasswordToken;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getResetPasswordTokenExpiry(): ?\DateTimeImmutable
    {
        return $this->resetPasswordTokenExpiry;
    }

    public function setResetPasswordTokenExpiry(?\DateTimeImmutable $resetPasswordTokenExpiry): static
    {
        $this->resetPasswordTokenExpiry = $resetPasswordTokenExpiry;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isResetPasswordTokenValid(): bool
    {
        if (!$this->resetPasswordToken || !$this->resetPasswordTokenExpiry) {
            return false;
        }
        return $this->resetPasswordTokenExpiry > new \DateTimeImmutable();
    }

    public function generateEmailVerificationToken(): string
    {
        $this->emailVerificationToken = bin2hex(random_bytes(32));
        $this->updatedAt = new \DateTimeImmutable();
        return $this->emailVerificationToken;
    }

    // Getters et Setters pour les nouveaux champs (remplacement EAV)

    public function getLicenceNumber(): ?string
    {
        return $this->licenceNumber;
    }

    public function setLicenceNumber(?string $licenceNumber): static
    {
        $this->licenceNumber = $licenceNumber;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getLicenceFile(): ?string
    {
        return $this->licenceFile;
    }

    public function setLicenceFile(?string $licenceFile): static
    {
        $this->licenceFile = $licenceFile;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getLicenceExpiry(): ?\DateTimeInterface
    {
        return $this->licenceExpiry;
    }

    public function setLicenceExpiry(?\DateTimeInterface $licenceExpiry): static
    {
        $this->licenceExpiry = $licenceExpiry;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isLicenceValid(): bool
    {
        if (!$this->licenceExpiry) {
            return false;
        }
        return $this->licenceExpiry >= new \DateTime('today');
    }

    public function getMedicalCertificateDate(): ?\DateTimeInterface
    {
        return $this->medicalCertificateDate;
    }

    public function setMedicalCertificateDate(?\DateTimeInterface $medicalCertificateDate): static
    {
        $this->medicalCertificateDate = $medicalCertificateDate;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getMedicalCertificateExpiry(): ?\DateTimeInterface
    {
        return $this->medicalCertificateExpiry;
    }

    public function setMedicalCertificateExpiry(?\DateTimeInterface $medicalCertificateExpiry): static
    {
        $this->medicalCertificateExpiry = $medicalCertificateExpiry;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getInsuranceNumber(): ?string
    {
        return $this->insuranceNumber;
    }

    public function setInsuranceNumber(?string $insuranceNumber): static
    {
        $this->insuranceNumber = $insuranceNumber;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getInsuranceExpiry(): ?\DateTimeInterface
    {
        return $this->insuranceExpiry;
    }

    public function setInsuranceExpiry(?\DateTimeInterface $insuranceExpiry): static
    {
        $this->insuranceExpiry = $insuranceExpiry;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getEmergencyContactName(): ?string
    {
        return $this->emergencyContactName;
    }

    public function setEmergencyContactName(?string $emergencyContactName): static
    {
        $this->emergencyContactName = $emergencyContactName;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getEmergencyContactPhone(): ?string
    {
        return $this->emergencyContactPhone;
    }

    public function setEmergencyContactPhone(?string $emergencyContactPhone): static
    {
        $this->emergencyContactPhone = $emergencyContactPhone;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): static
    {
        $this->dateOfBirth = $dateOfBirth;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    // Getters/Setters pour la vérification CACI

    public function getMedicalCertificateVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->medicalCertificateVerifiedAt;
    }

    public function setMedicalCertificateVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->medicalCertificateVerifiedAt = $verifiedAt;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getMedicalCertificateVerifiedBy(): ?User
    {
        return $this->medicalCertificateVerifiedBy;
    }

    public function setMedicalCertificateVerifiedBy(?User $verifiedBy): static
    {
        $this->medicalCertificateVerifiedBy = $verifiedBy;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Vérifie le CACI (appelé par un DP)
     */
    public function verifyCaci(User $verifiedBy): static
    {
        $this->medicalCertificateVerifiedAt = new \DateTimeImmutable();
        $this->medicalCertificateVerifiedBy = $verifiedBy;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Réinitialise la vérification CACI (quand le plongeur déclare une nouvelle date)
     */
    public function resetCaciVerification(): static
    {
        $this->medicalCertificateVerifiedAt = null;
        $this->medicalCertificateVerifiedBy = null;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Le CACI a-t-il été vérifié par un DP ?
     */
    public function isCaciVerified(): bool
    {
        return $this->medicalCertificateVerifiedAt !== null;
    }

    /**
     * Le CACI est-il en attente de vérification ?
     * (date déclarée mais pas encore vérifiée par un DP)
     */
    public function isCaciPendingVerification(): bool
    {
        return $this->medicalCertificateExpiry !== null && !$this->isCaciVerified();
    }

    /**
     * Retourne le statut du CACI
     * - 'missing' : pas de date déclarée
     * - 'pending' : date déclarée, en attente de vérification
     * - 'expired' : date expirée
     * - 'valid' : vérifié et non expiré
     */
    public function getCaciStatus(): string
    {
        if (!$this->medicalCertificateExpiry) {
            return 'missing';
        }

        if ($this->medicalCertificateExpiry < new \DateTime('today')) {
            return 'expired';
        }

        if (!$this->isCaciVerified()) {
            return 'pending';
        }

        return 'valid';
    }

    /**
     * Vérifie si le certificat médical est valide (vérifié ET non expiré)
     */
    public function isMedicalCertificateValid(): bool
    {
        return $this->getCaciStatus() === 'valid';
    }

    /**
     * Vérifie si le plongeur peut s'inscrire aux événements
     * (CACI valide OU en attente de vérification ET non expiré)
     */
    public function canRegisterToEvents(): bool
    {
        $status = $this->getCaciStatus();
        return $status === 'valid' || $status === 'pending';
    }

    /**
     * Vérifie si l'assurance est valide (non expirée)
     */
    public function isInsuranceValid(): bool
    {
        if (!$this->insuranceExpiry) {
            return false;
        }
        return $this->insuranceExpiry >= new \DateTime('today');
    }

    public function getAvatarFile(): ?string
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(?string $avatarFile): static
    {
        $this->avatarFile = $avatarFile;
        return $this;
    }
}