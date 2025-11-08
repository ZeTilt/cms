<?php

namespace App\Entity;

use App\Repository\EventTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventTypeRepository::class)]
class EventType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 7)]
    private ?string $color = null;

    #[ORM\Column(nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $requiresDivingDirector = false;

    #[ORM\Column(type: 'boolean')]
    private bool $requiresPilot = false;

    #[ORM\Column(type: 'boolean')]
    private bool $requiresLifeguard = false;

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'eventType')]
    private Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;
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

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setEventType($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getEventType() === $this) {
                $event->setEventType(null);
            }
        }

        return $this;
    }

    public function requiresDivingDirector(): bool
    {
        return $this->requiresDivingDirector;
    }

    public function setRequiresDivingDirector(bool $requiresDivingDirector): static
    {
        $this->requiresDivingDirector = $requiresDivingDirector;
        return $this;
    }

    public function requiresPilot(): bool
    {
        return $this->requiresPilot;
    }

    public function setRequiresPilot(bool $requiresPilot): static
    {
        $this->requiresPilot = $requiresPilot;
        return $this;
    }

    public function requiresLifeguard(): bool
    {
        return $this->requiresLifeguard;
    }

    public function setRequiresLifeguard(bool $requiresLifeguard): static
    {
        $this->requiresLifeguard = $requiresLifeguard;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}