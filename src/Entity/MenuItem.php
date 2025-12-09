<?php

namespace App\Entity;

use App\Repository\MenuItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuItemRepository::class)]
#[ORM\Table(name: 'menu_item')]
class MenuItem
{
    public const TYPE_ROUTE = 'route';
    public const TYPE_PAGE = 'page';
    public const TYPE_URL = 'url';
    public const TYPE_DROPDOWN = 'dropdown';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Menu::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Menu $menu = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $children;

    #[ORM\Column(length: 100)]
    private ?string $label = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_ROUTE;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $routeParams = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customUrl = null;

    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Page $page = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $roles = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $cssClass = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $openInNewTab = false;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): static
    {
        $this->menu = $menu;
        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @return Collection<int, self>
     */
    public function getActiveChildren(): Collection
    {
        return $this->children->filter(fn(self $child) => $child->isActive());
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
            $child->setMenu($this->menu);
        }
        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }
        return $this;
    }

    public function hasChildren(): bool
    {
        return !$this->children->isEmpty();
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
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

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): static
    {
        $this->route = $route;
        return $this;
    }

    public function getRouteParams(): ?array
    {
        return $this->routeParams;
    }

    public function setRouteParams(?array $routeParams): static
    {
        $this->routeParams = $routeParams;
        return $this;
    }

    public function getCustomUrl(): ?string
    {
        return $this->customUrl;
    }

    public function setCustomUrl(?string $customUrl): static
    {
        $this->customUrl = $customUrl;
        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        $this->page = $page;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
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

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(?array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function setCssClass(?string $cssClass): static
    {
        $this->cssClass = $cssClass;
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

    public function isOpenInNewTab(): bool
    {
        return $this->openInNewTab;
    }

    public function setOpenInNewTab(bool $openInNewTab): static
    {
        $this->openInNewTab = $openInNewTab;
        return $this;
    }

    /**
     * Check if user has access to this menu item based on roles
     */
    public function isAccessibleBy(?array $userRoles): bool
    {
        // If no roles restriction, accessible to everyone
        if (empty($this->roles)) {
            return true;
        }

        // If user has no roles, check if item requires roles
        if (empty($userRoles)) {
            return false;
        }

        // Check if user has any of the required roles
        return !empty(array_intersect($this->roles, $userRoles));
    }

    /**
     * Get the URL for this menu item
     */
    public function getUrl(): ?string
    {
        return match ($this->type) {
            self::TYPE_URL => $this->customUrl,
            self::TYPE_PAGE => $this->page?->getSlug(),
            default => null,
        };
    }

    public function isDropdown(): bool
    {
        return $this->type === self::TYPE_DROPDOWN || $this->hasChildren();
    }

    public function __toString(): string
    {
        return $this->label ?? '';
    }
}
