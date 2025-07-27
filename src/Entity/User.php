<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: UserType::class, inversedBy: 'users')]
    private ?UserType $userType = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Gallery::class, orphanRemoval: true)]
    private Collection $galleries;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserAttribute::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userAttributes;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->galleries = new ArrayCollection();
        $this->userAttributes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
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

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
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

    public function getUserType(): ?UserType
    {
        return $this->userType;
    }

    public function setUserType(?UserType $userType): static
    {
        $this->userType = $userType;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * @return Collection<int, UserAttribute>
     */
    public function getUserAttributes(): Collection
    {
        return $this->userAttributes;
    }

    public function addUserAttribute(UserAttribute $userAttribute): static
    {
        if (!$this->userAttributes->contains($userAttribute)) {
            $this->userAttributes->add($userAttribute);
            $userAttribute->setUser($this);
        }

        return $this;
    }

    public function removeUserAttribute(UserAttribute $userAttribute): static
    {
        if ($this->userAttributes->removeElement($userAttribute)) {
            if ($userAttribute->getUser() === $this) {
                $userAttribute->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Get user attribute by key
     */
    public function getUserAttributeByKey(string $key): ?UserAttribute
    {
        foreach ($this->userAttributes as $attribute) {
            if ($attribute->getAttributeKey() === $key) {
                return $attribute;
            }
        }
        return null;
    }

    /**
     * Get user attribute value by key
     */
    public function getUserAttributeValue(string $key): mixed
    {
        $attribute = $this->getUserAttributeByKey($key);
        return $attribute ? $attribute->getTypedValue() : null;
    }

    /**
     * Set user attribute value by key
     */
    public function setUserAttributeValue(string $key, mixed $value, string $type = 'text'): static
    {
        $attribute = $this->getUserAttributeByKey($key);
        
        if (!$attribute) {
            $attribute = new UserAttribute();
            $attribute->setUser($this);
            $attribute->setAttributeKey($key);
            $attribute->setAttributeType($type);
            $this->addUserAttribute($attribute);
        }
        
        $attribute->setTypedValue($value);
        return $this;
    }

    /**
     * Initialize user attributes based on user type
     */
    public function initializeAttributesFromType(): static
    {
        if (!$this->userType) {
            return $this;
        }

        foreach ($this->userType->getAttributes() as $typeAttribute) {
            if (!$this->getUserAttributeByKey($typeAttribute->getAttributeKey())) {
                $userAttribute = new UserAttribute();
                $userAttribute->setUser($this);
                $userAttribute->setAttributeKey($typeAttribute->getAttributeKey());
                $userAttribute->setAttributeType($typeAttribute->getAttributeType());
                
                if ($typeAttribute->getDefaultValue()) {
                    $userAttribute->setTypedValue($typeAttribute->getDefaultValue());
                }
                
                $this->addUserAttribute($userAttribute);
            }
        }

        return $this;
    }

    /**
     * Get missing required attributes for this user
     */
    public function getMissingRequiredAttributes(): array
    {
        if (!$this->userType) {
            return [];
        }

        $missing = [];
        foreach ($this->userType->getRequiredAttributes() as $typeAttribute) {
            $userAttribute = $this->getUserAttributeByKey($typeAttribute->getAttributeKey());
            if (!$userAttribute || empty($userAttribute->getAttributeValue())) {
                $missing[] = $typeAttribute;
            }
        }

        return $missing;
    }

    /**
     * Check if user has all required attributes filled
     */
    public function hasAllRequiredAttributes(): bool
    {
        return empty($this->getMissingRequiredAttributes());
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

    public function getMetadataValue(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    public function setMetadataValue(string $key, mixed $value): static
    {
        if ($this->metadata === null) {
            $this->metadata = [];
        }
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get username by concatenating first name and last name
     */
    public function getUsername(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
}