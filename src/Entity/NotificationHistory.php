<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\NotificationHistoryRepository::class)]
#[ORM\Table(name: 'notification_history')]
#[ORM\Index(name: 'idx_user_created', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'idx_event_created', columns: ['event_id', 'created_at'])]
class NotificationHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Event $event = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type; // event_registration, event_cancellation, waiting_list_promotion

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'sent'; // sent, delivered, opened, clicked, failed

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $openedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $clickedAt = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $groupTag = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and setters

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

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;
        if ($deliveredAt && $this->status === 'sent') {
            $this->status = 'delivered';
        }
        return $this;
    }

    public function getOpenedAt(): ?\DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function setOpenedAt(?\DateTimeImmutable $openedAt): static
    {
        $this->openedAt = $openedAt;
        if ($openedAt && in_array($this->status, ['sent', 'delivered'])) {
            $this->status = 'opened';
        }
        return $this;
    }

    public function getClickedAt(): ?\DateTimeImmutable
    {
        return $this->clickedAt;
    }

    public function setClickedAt(?\DateTimeImmutable $clickedAt): static
    {
        $this->clickedAt = $clickedAt;
        if ($clickedAt) {
            $this->status = 'clicked';
        }
        return $this;
    }

    public function getGroupTag(): ?string
    {
        return $this->groupTag;
    }

    public function setGroupTag(?string $groupTag): static
    {
        $this->groupTag = $groupTag;
        return $this;
    }

    public function markAsDelivered(): static
    {
        return $this->setDeliveredAt(new \DateTimeImmutable());
    }

    public function markAsOpened(): static
    {
        return $this->setOpenedAt(new \DateTimeImmutable());
    }

    public function markAsClicked(): static
    {
        return $this->setClickedAt(new \DateTimeImmutable());
    }

    public function markAsFailed(): static
    {
        $this->status = 'failed';
        return $this;
    }
}
