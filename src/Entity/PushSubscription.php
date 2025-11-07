<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\PushSubscriptionRepository::class)]
#[ORM\Table(name: 'push_subscriptions')]
#[ORM\UniqueConstraint(name: 'unique_subscription', columns: ['user_id', 'endpoint'])]
class PushSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 500)]
    private string $endpoint;

    #[ORM\Column(type: 'string', length: 255)]
    private string $publicKey;

    #[ORM\Column(type: 'string', length: 255)]
    private string $authToken;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    // Préférences de notifications
    #[ORM\Column(type: 'boolean')]
    private bool $notifyEventRegistration = true;

    #[ORM\Column(type: 'boolean')]
    private bool $notifyEventCancellation = true;

    #[ORM\Column(type: 'boolean')]
    private bool $notifyWaitingListPromotion = true;

    #[ORM\Column(type: 'boolean')]
    private bool $notifyAsDP = true;

    #[ORM\Column(type: 'boolean')]
    private bool $notifyEventReminder = true;

    #[ORM\Column(type: 'boolean')]
    private bool $notifyNewEvent = true;

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

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): static
    {
        $this->publicKey = $publicKey;
        return $this;
    }

    public function getAuthToken(): string
    {
        return $this->authToken;
    }

    public function setAuthToken(string $authToken): static
    {
        $this->authToken = $authToken;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    public function updateLastUsedAt(): static
    {
        $this->lastUsedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isNotifyEventRegistration(): bool
    {
        return $this->notifyEventRegistration;
    }

    public function setNotifyEventRegistration(bool $notifyEventRegistration): static
    {
        $this->notifyEventRegistration = $notifyEventRegistration;
        return $this;
    }

    public function isNotifyEventCancellation(): bool
    {
        return $this->notifyEventCancellation;
    }

    public function setNotifyEventCancellation(bool $notifyEventCancellation): static
    {
        $this->notifyEventCancellation = $notifyEventCancellation;
        return $this;
    }

    public function isNotifyWaitingListPromotion(): bool
    {
        return $this->notifyWaitingListPromotion;
    }

    public function setNotifyWaitingListPromotion(bool $notifyWaitingListPromotion): static
    {
        $this->notifyWaitingListPromotion = $notifyWaitingListPromotion;
        return $this;
    }

    public function isNotifyAsDP(): bool
    {
        return $this->notifyAsDP;
    }

    public function setNotifyAsDP(bool $notifyAsDP): static
    {
        $this->notifyAsDP = $notifyAsDP;
        return $this;
    }

    public function isNotifyEventReminder(): bool
    {
        return $this->notifyEventReminder;
    }

    public function setNotifyEventReminder(bool $notifyEventReminder): static
    {
        $this->notifyEventReminder = $notifyEventReminder;
        return $this;
    }

    public function isNotifyNewEvent(): bool
    {
        return $this->notifyNewEvent;
    }

    public function setNotifyNewEvent(bool $notifyNewEvent): static
    {
        $this->notifyNewEvent = $notifyNewEvent;
        return $this;
    }
}
