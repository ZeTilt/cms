<?php

namespace App\Entity;

use App\Trait\HasDynamicAttributesTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use HasDynamicAttributesTrait;
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

    #[ORM\Column(length: 20)]
    private string $status = 'pending_approval'; // pending_approval, approved, rejected

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


    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserRole::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userRoles;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->galleries = new ArrayCollection();
        $this->userAttributes = new ArrayCollection();
        $this->userRoles = new ArrayCollection();
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
        // Commencer avec les rôles de base (legacy)
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        // Ajouter les rôles du nouveau système hiérarchique
        foreach ($this->getUserRoles() as $userRole) {
            if ($userRole->isActive()) {
                $roles[] = $userRole->getRole()->getName();
            }
        }

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

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(): static
    {
        $this->status = 'approved';
        $this->active = true;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function reject(): static
    {
        $this->status = 'rejected';
        $this->active = false;
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


    /**
     * Get username by concatenating first name and last name
     */
    public function getUsername(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    // ========== MULTI-ROLES MANAGEMENT ==========

    /**
     * @return Collection<int, UserRole>
     */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    public function addUserRole(UserRole $userRole): static
    {
        if (!$this->userRoles->contains($userRole)) {
            $this->userRoles->add($userRole);
            $userRole->setUser($this);
        }
        return $this;
    }

    public function removeUserRole(UserRole $userRole): static
    {
        if ($this->userRoles->removeElement($userRole)) {
            if ($userRole->getUser() === $this) {
                $userRole->setUser(null);
            }
        }
        return $this;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique (nouveau système)
     */
    public function hasUserRole(string $roleName): bool
    {
        foreach ($this->userRoles as $userRole) {
            if ($userRole->isActive() && $userRole->getRole()->getName() === $roleName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtient tous les rôles actifs avec leurs objets Role
     */
    public function getActiveRoles(): array
    {
        $activeRoles = [];
        foreach ($this->userRoles as $userRole) {
            if ($userRole->isActive()) {
                $activeRoles[] = $userRole->getRole();
            }
        }
        return $activeRoles;
    }

    /**
     * Obtient le rôle le plus élevé dans la hiérarchie
     */
    public function getHighestRole(): ?Role
    {
        $highestRole = null;
        $highestHierarchy = -1;

        foreach ($this->getActiveRoles() as $role) {
            if ($role->getHierarchy() > $highestHierarchy) {
                $highestHierarchy = $role->getHierarchy();
                $highestRole = $role;
            }
        }

        return $highestRole;
    }

    /**
     * Vérifie si l'utilisateur a au moins le niveau hiérarchique requis
     */
    public function hasMinimumHierarchyLevel(int $requiredLevel): bool
    {
        $highestRole = $this->getHighestRole();
        return $highestRole && $highestRole->getHierarchy() >= $requiredLevel;
    }

    /**
     * Retourne l'affichage des rôles pour l'interface
     */
    public function getDisplayRoles(): array
    {
        return array_map(
            fn(Role $role) => $role->getDisplayName(),
            $this->getActiveRoles()
        );
    }

    // ========== EAV MIGRATION METHODS ==========


    /**
     * Devine le type d'attribut basé sur la clé et la valeur
     */
    private function guessAttributeType(string $key, mixed $value): string
    {
        // Types basés sur les clés connues du système d'inscription
        $knownTypes = [
            'registration_status' => 'select',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'rejected_by' => 'integer', 
            'rejected_at' => 'datetime',
            'rejection_reason' => 'text',
            'email_verified' => 'boolean',
            'verification_token' => 'text',
            'birth_date' => 'date',
            'phone_number' => 'text',
            'emergency_contact' => 'text',
            'medical_certificate' => 'file',
            'diving_level' => 'select',
            'is_instructor' => 'boolean'
        ];

        if (isset($knownTypes[$key])) {
            return $knownTypes[$key];
        }

        // Types basés sur la valeur
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_array($value)) {
            return 'json';
        }
        
        // Détecter les dates
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $value)) {
            return strpos($value, ' ') !== false ? 'datetime' : 'date';
        }

        return 'text';
    }

    /**
     * Obtient une valeur de métadonnée via EAV
     */
    public function getMetadataValueEav(string $key): mixed
    {
        // Utiliser uniquement le système EAV
        if ($this->eavService && $this->getId()) {
            $eavAttributes = $this->eavService->getEntityAttributes('User', $this->getId());
            return $eavAttributes[$key] ?? null;
        }

        return null;
    }

    /**
     * Définit une valeur de métadonnée via EAV
     */
    public function setMetadataValueEav(string $key, mixed $value, string $type = 'text'): static
    {
        if ($this->eavService && $this->getId()) {
            $this->setDynamicAttribute($key, $value, $type);
        }
        
        return $this;
    }

    /**
     * Obtient un attribut dynamique via le système EAV
     */
    public function getDynamicAttribute(string $key): mixed
    {
        if (!$this->getId()) {
            return null;
        }
        
        // Accès direct à la base de données SQLite car EavService n'est pas injecté dans les entités
        try {
            $dbPath = __DIR__ . '/../../var/data_dev.db';
            $pdo = new \PDO('sqlite:' . $dbPath);
            $stmt = $pdo->prepare('SELECT attribute_value FROM entity_attributes WHERE entity_type = ? AND entity_id = ? AND attribute_name = ?');
            $stmt->execute(['User', $this->getId(), $key]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result ? $result['attribute_value'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Définit un attribut dynamique via le système EAV
     */
    public function setDynamicAttribute(string $key, mixed $value, string $type = 'text'): static
    {
        if ($this->eavService && $this->getId()) {
            $this->eavService->setAttribute('User', $this->getId(), $key, $value, $type);
        }
        
        return $this;
    }
}