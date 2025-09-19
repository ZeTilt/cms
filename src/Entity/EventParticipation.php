<?php

namespace App\Entity;

use App\Repository\EventParticipationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventParticipationRepository::class)]
class EventParticipation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $participant = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'registered';

    #[ORM\Column]
    private ?\DateTimeImmutable $registrationDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmationDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $meetingPoint = null; // 'club' ou 'site'

    #[ORM\Column]
    private ?bool $isWaitingList = false;

    public function __construct()
    {
        $this->registrationDate = new \DateTimeImmutable();
        $this->status = 'registered';
        $this->isWaitingList = false;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getParticipant(): ?User
    {
        return $this->participant;
    }

    public function setParticipant(?User $participant): static
    {
        $this->participant = $participant;
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

    public function getRegistrationDate(): ?\DateTimeImmutable
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTimeImmutable $registrationDate): static
    {
        $this->registrationDate = $registrationDate;
        return $this;
    }

    public function getConfirmationDate(): ?\DateTimeImmutable
    {
        return $this->confirmationDate;
    }

    public function setConfirmationDate(?\DateTimeImmutable $confirmationDate): static
    {
        $this->confirmationDate = $confirmationDate;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            'registered' => 'Inscrit',
            'confirmed' => 'Confirmé',
            'cancelled' => 'Annulé',
            'no_show' => 'Absent',
            'completed' => 'Participé',
            default => 'Inconnu'
        };
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['registered', 'confirmed']);
    }

    public function getMeetingPoint(): ?string
    {
        return $this->meetingPoint;
    }

    public function setMeetingPoint(?string $meetingPoint): static
    {
        $this->meetingPoint = $meetingPoint;
        return $this;
    }

    public function isWaitingList(): ?bool
    {
        return $this->isWaitingList;
    }

    public function setIsWaitingList(bool $isWaitingList): static
    {
        $this->isWaitingList = $isWaitingList;
        return $this;
    }

    public function getMeetingPointDisplayName(): string
    {
        return match($this->meetingPoint) {
            'club' => 'RDV au club',
            'site' => 'RDV sur site',
            default => 'Non défini'
        };
    }
}