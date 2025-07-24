<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_types')]
class UserType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $displayName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(type: 'json')]
    private array $config = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'userType', targetEntity: UserTypeAttribute::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['displayOrder' => 'ASC'])]
    private Collection $attributes;

    #[ORM\OneToMany(mappedBy: 'userType', targetEntity: User::class)]
    private Collection $users;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->attributes = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
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

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): static
    {
        $this->config = $config;
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
     * @return Collection<int, UserTypeAttribute>
     */
    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function addAttribute(UserTypeAttribute $attribute): static
    {
        if (!$this->attributes->contains($attribute)) {
            $this->attributes->add($attribute);
            $attribute->setUserType($this);
        }

        return $this;
    }

    public function removeAttribute(UserTypeAttribute $attribute): static
    {
        if ($this->attributes->removeElement($attribute)) {
            if ($attribute->getUserType() === $this) {
                $attribute->setUserType(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setUserType($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getUserType() === $this) {
                $user->setUserType(null);
            }
        }

        return $this;
    }

    /**
     * Get required attributes for this user type
     */
    public function getRequiredAttributes(): Collection
    {
        return $this->attributes->filter(fn(UserTypeAttribute $attr) => $attr->isRequired());
    }

    /**
     * Get optional attributes for this user type
     */
    public function getOptionalAttributes(): Collection
    {
        return $this->attributes->filter(fn(UserTypeAttribute $attr) => !$attr->isRequired());
    }

    /**
     * Get attribute by key
     */
    public function getAttributeByKey(string $key): ?UserTypeAttribute
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->getAttributeKey() === $key) {
                return $attribute;
            }
        }
        return null;
    }

    /**
     * Check if this user type has a specific attribute
     */
    public function hasAttribute(string $key): bool
    {
        return $this->getAttributeByKey($key) !== null;
    }

    /**
     * Get the count of users with this type
     */
    public function getUserCount(): int
    {
        return $this->users->count();
    }

    /**
     * Get badge color for UI display
     */
    public function getBadgeColor(): string
    {
        return match (strtolower($this->name)) {
            'client' => 'bg-green-100 text-green-800',
            'photographer' => 'bg-purple-100 text-purple-800',
            'vendor' => 'bg-blue-100 text-blue-800',
            'admin' => 'bg-red-100 text-red-800',
            'editor' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function __toString(): string
    {
        return $this->displayName;
    }
}