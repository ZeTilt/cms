<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'roles')]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $name = '';

    #[ORM\Column(length: 100)]
    private string $displayName = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private int $hierarchy = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'role', targetEntity: UserRole::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userRoles;

    #[ORM\ManyToMany(targetEntity: Permission::class, inversedBy: 'roles')]
    #[ORM\JoinTable(name: 'role_permissions')]
    private Collection $permissions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->userRoles = new ArrayCollection();
        $this->permissions = new ArrayCollection();
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

    public function getHierarchy(): int
    {
        return $this->hierarchy;
    }

    public function setHierarchy(int $hierarchy): static
    {
        $this->hierarchy = $hierarchy;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }

        return $this;
    }

    public function removePermission(Permission $permission): static
    {
        $this->permissions->removeElement($permission);

        return $this;
    }

    public function hasPermission(string $permissionName): bool
    {
        foreach ($this->permissions as $permission) {
            if ($permission->getName() === $permissionName || $permission->getFullKey() === $permissionName) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Obtient les permissions sous forme de tableau pour la compatibilité
     */
    public function getPermissionNames(): array
    {
        return $this->permissions->map(fn(Permission $p) => $p->getName())->toArray();
    }


    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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
            $userRole->setRole($this);
        }
        return $this;
    }

    public function removeUserRole(UserRole $userRole): static
    {
        if ($this->userRoles->removeElement($userRole)) {
            if ($userRole->getRole() === $this) {
                $userRole->setRole(null);
            }
        }
        return $this;
    }

    /**
     * Obtient tous les utilisateurs qui ont ce rôle
     * @return User[]
     */
    public function getUsers(): array
    {
        $users = [];
        foreach ($this->userRoles as $userRole) {
            if ($userRole->isActive()) {
                $users[] = $userRole->getUser();
            }
        }
        return $users;
    }

    /**
     * Vérifie si ce rôle a une hiérarchie supérieure ou égale à un autre rôle
     */
    public function hasHierarchyLevel(int $level): bool
    {
        return $this->hierarchy >= $level;
    }

    /**
     * Retourne le badge CSS pour l'affichage
     */
    public function getBadgeColor(): string
    {
        return match($this->name) {
            'ROLE_SUPER_ADMIN' => 'bg-purple-100 text-purple-800',
            'ROLE_ADMIN' => 'bg-red-100 text-red-800',
            'ROLE_DIRECTEUR_PLONGEE' => 'bg-blue-100 text-blue-800',
            'ROLE_PILOTE' => 'bg-green-100 text-green-800',
            'ROLE_PLONGEUR' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}