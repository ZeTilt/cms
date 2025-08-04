<?php

namespace App\Entity;

use App\Repository\WidgetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WidgetRepository::class)]
#[ORM\Table(name: 'widgets')]
class Widget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private string $type = 'html';

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $settings = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column]
    private bool $cacheable = true;

    #[ORM\Column]
    private int $cacheTime = 3600; // 1 heure par défaut

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->settings = [];
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
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSettings(): ?array
    {
        return $this->settings;
    }

    public function setSettings(?array $settings): static
    {
        $this->settings = $settings;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, mixed $value): static
    {
        if ($this->settings === null) {
            $this->settings = [];
        }
        $this->settings[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
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

    public function isCacheable(): bool
    {
        return $this->cacheable;
    }

    public function setCacheable(bool $cacheable): static
    {
        $this->cacheable = $cacheable;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCacheTime(): int
    {
        return $this->cacheTime;
    }

    public function setCacheTime(int $cacheTime): static
    {
        $this->cacheTime = $cacheTime;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * Obtient les types de widgets disponibles
     */
    public static function getAvailableTypes(): array
    {
        return [
            'html' => 'HTML personnalisé',
            'iframe' => 'iFrame externe',
            'script' => 'Script JavaScript',
            'weather' => 'Widget météo',
            'map' => 'Carte interactive',
            'social' => 'Réseaux sociaux',
            'calendar' => 'Calendrier',
            'custom' => 'Widget personnalisé'
        ];
    }

    /**
     * Obtient les catégories disponibles
     */
    public static function getAvailableCategories(): array
    {
        return [
            'navigation' => 'Navigation',
            'content' => 'Contenu',
            'media' => 'Média',
            'social' => 'Social',
            'promotional' => 'Promotionnel',
            'tracking' => 'Suivi/Analytics',
            'utility' => 'Utilitaire',
            'external' => 'Service externe',
            'test' => 'Test',
            'custom' => 'Personnalisé'
        ];
    }

    /**
     * Vérifie si le contenu est sécurisé
     */
    public function isContentSecure(): bool
    {
        $content = $this->content;
        
        // Vérifier les balises potentiellement dangereuses
        $dangerousTags = ['<script', '<object', '<embed', '<link'];
        
        foreach ($dangerousTags as $tag) {
            if (stripos($content, $tag) !== false && $this->type !== 'script') {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Génère le shortcode pour ce widget
     */
    public function getShortcode(): string
    {
        return sprintf('[widget id="%d"]', $this->id);
    }

    /**
     * Génère le shortcode par nom pour ce widget
     */
    public function getShortcodeByName(): string
    {
        return sprintf('[widget name="%s"]', $this->name);
    }
}