<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'business_contacts')]
class BusinessContact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(length: 50)]
    private string $status = 'prospect'; // prospect, lead, client, inactive

    #[ORM\Column(length: 50)]
    private string $source = 'website'; // website, referral, social, advertising, other

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'json')]
    private array $tags = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $address = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $customFields = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastContactDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $nextFollowUpDate = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $assignedTo;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;
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

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;
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

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    public function getAddress(): ?array
    {
        return $this->address;
    }

    public function setAddress(?array $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    public function setCustomFields(?array $customFields): static
    {
        $this->customFields = $customFields;
        return $this;
    }

    public function getLastContactDate(): ?\DateTimeImmutable
    {
        return $this->lastContactDate;
    }

    public function setLastContactDate(?\DateTimeImmutable $lastContactDate): static
    {
        $this->lastContactDate = $lastContactDate;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getNextFollowUpDate(): ?\DateTimeImmutable
    {
        return $this->nextFollowUpDate;
    }

    public function setNextFollowUpDate(?\DateTimeImmutable $nextFollowUpDate): static
    {
        $this->nextFollowUpDate = $nextFollowUpDate;
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

    public function getAssignedTo(): User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    /**
     * Check if follow-up is overdue
     */
    public function isFollowUpOverdue(): bool
    {
        if (!$this->nextFollowUpDate) {
            return false;
        }

        return $this->nextFollowUpDate < new \DateTimeImmutable();
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'prospect' => 'bg-blue-100 text-blue-800',
            'lead' => 'bg-orange-100 text-orange-800',
            'client' => 'bg-green-100 text-green-800',
            'inactive' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get source badge color for UI
     */
    public function getSourceBadgeColor(): string
    {
        return match ($this->source) {
            'website' => 'bg-purple-100 text-purple-800',
            'referral' => 'bg-indigo-100 text-indigo-800',
            'social' => 'bg-pink-100 text-pink-800',
            'advertising' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}