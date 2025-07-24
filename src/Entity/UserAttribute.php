<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_attributes')]
class UserAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 100)]
    private string $attributeKey;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $attributeValue = null;

    #[ORM\Column(length: 50)]
    private string $attributeType = 'text'; // text, number, boolean, date, json

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

    public function getAttributeKey(): string
    {
        return $this->attributeKey;
    }

    public function setAttributeKey(string $attributeKey): static
    {
        $this->attributeKey = $attributeKey;
        return $this;
    }

    public function getAttributeValue(): ?string
    {
        return $this->attributeValue;
    }

    public function setAttributeValue(?string $attributeValue): static
    {
        $this->attributeValue = $attributeValue;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAttributeType(): string
    {
        return $this->attributeType;
    }

    public function setAttributeType(string $attributeType): static
    {
        $this->attributeType = $attributeType;
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
     * Get the typed value based on attribute type
     */
    public function getTypedValue(): mixed
    {
        return match ($this->attributeType) {
            'boolean' => filter_var($this->attributeValue, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($this->attributeValue) ? (float) $this->attributeValue : null,
            'date' => $this->attributeValue ? new \DateTime($this->attributeValue) : null,
            'json' => $this->attributeValue ? json_decode($this->attributeValue, true) : null,
            default => $this->attributeValue,
        };
    }

    /**
     * Set the value with automatic type conversion
     */
    public function setTypedValue(mixed $value): static
    {
        $this->attributeValue = match ($this->attributeType) {
            'boolean' => $value ? '1' : '0',
            'number' => (string) $value,
            'date' => $value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : (string) $value,
            'json' => json_encode($value),
            default => (string) $value,
        };
        
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}