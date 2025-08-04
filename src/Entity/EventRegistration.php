<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_registrations')]
class EventRegistration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
    private Event $event;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 20)]
    private string $status = 'registered'; // registered, waiting_list, cancelled, no_show

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private \DateTimeImmutable $registeredAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(options: ['default' => 1])]
    private int $numberOfSpots = 1;

    #[ORM\Column(length: 20, options: ['default' => 'club'])]
    private string $departureLocation = 'club'; // 'club' or 'dock'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $registrationComment = null;

    public function __construct()
    {
        $this->registeredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): static
    {
        $this->event = $event;
        return $this;
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

    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeImmutable $registeredAt): static
    {
        $this->registeredAt = $registeredAt;
        return $this;
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

    public function isOnWaitingList(): bool
    {
        return $this->status === 'waiting_list';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['registered', 'waiting_list']);
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'registered' => 'Inscrit',
            'waiting_list' => 'Liste d\'attente',
            'cancelled' => 'Annulé',
            'no_show' => 'Absent',
            default => $this->status
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'registered' => 'bg-green-100 text-green-800',
            'waiting_list' => 'bg-yellow-100 text-yellow-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'no_show' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getNumberOfSpots(): int
    {
        return $this->numberOfSpots;
    }

    public function setNumberOfSpots(int $numberOfSpots): static
    {
        $this->numberOfSpots = max(1, $numberOfSpots); // Minimum 1 place
        return $this;
    }

    public function getDepartureLocation(): string
    {
        return $this->departureLocation;
    }

    public function setDepartureLocation(string $departureLocation): static
    {
        $this->departureLocation = in_array($departureLocation, ['club', 'dock']) ? $departureLocation : 'club';
        return $this;
    }

    public function getRegistrationComment(): ?string
    {
        return $this->registrationComment;
    }

    public function setRegistrationComment(?string $registrationComment): static
    {
        $this->registrationComment = $registrationComment;
        return $this;
    }

    public function getDepartureLocationLabel(): string
    {
        return match($this->departureLocation) {
            'club' => 'Départ du club',
            'dock' => 'Départ au quai',
            default => 'Non spécifié'
        };
    }
}