<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bookings')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Service $service = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Event $event = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending'; // pending, confirmed, cancelled, completed, no_show

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $bookingDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(nullable: true)]
    private ?int $participants = 1;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $totalPrice = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $paymentStatus = null; // pending, paid, refunded, failed

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $customerInfo = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
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

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
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

    public function getBookingDate(): ?\DateTimeImmutable
    {
        return $this->bookingDate;
    }

    public function setBookingDate(?\DateTimeImmutable $bookingDate): static
    {
        $this->bookingDate = $bookingDate;
        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getParticipants(): ?int
    {
        return $this->participants;
    }

    public function setParticipants(?int $participants): static
    {
        $this->participants = $participants;
        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCustomerInfo(): ?array
    {
        return $this->customerInfo;
    }

    public function setCustomerInfo(?array $customerInfo): static
    {
        $this->customerInfo = $customerInfo;
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

    // Helper methods

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'cancelled' => 'Annulé',
            'completed' => 'Terminé',
            'no_show' => 'Absent',
            default => $this->status
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'confirmed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'completed' => 'bg-blue-100 text-blue-800',
            'no_show' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getPaymentStatusLabel(): string
    {
        return match($this->paymentStatus) {
            'pending' => 'En attente',
            'paid' => 'Payé',
            'refunded' => 'Remboursé',
            'failed' => 'Échec',
            default => $this->paymentStatus ?? 'Non défini'
        };
    }

    public function getPaymentStatusColor(): string
    {
        return match($this->paymentStatus) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'paid' => 'bg-green-100 text-green-800',
            'refunded' => 'bg-blue-100 text-blue-800',
            'failed' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getBookableItem(): ?object
    {
        return $this->service ?? $this->event;
    }

    public function getBookableItemName(): string
    {
        if ($this->service) {
            return $this->service->getName();
        } elseif ($this->event) {
            return $this->event->getTitle();
        }
        return 'Unknown';
    }

    public function getBookableItemType(): string
    {
        if ($this->service) {
            return 'service';
        } elseif ($this->event) {
            return 'event';
        }
        return 'unknown';
    }

    public function canBeCancelled(): bool
    {
        if (!in_array($this->status, ['pending', 'confirmed'])) {
            return false;
        }

        // Allow cancellation up to 24 hours before booking
        if ($this->startTime) {
            $cancelDeadline = $this->startTime->modify('-24 hours');
            return new \DateTimeImmutable() <= $cancelDeadline;
        }

        return true;
    }

    public function getDuration(): ?int
    {
        if (!$this->startTime || !$this->endTime) {
            return null;
        }

        return $this->endTime->getTimestamp() - $this->startTime->getTimestamp();
    }

    public function getFormattedDuration(): ?string
    {
        $seconds = $this->getDuration();
        if (!$seconds) {
            return null;
        }

        $hours = intval($seconds / 3600);
        $minutes = intval(($seconds % 3600) / 60);

        if ($hours > 0 && $minutes > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        } elseif ($hours > 0) {
            return sprintf('%dh', $hours);
        } else {
            return sprintf('%dm', $minutes);
        }
    }
}