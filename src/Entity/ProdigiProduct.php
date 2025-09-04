<?php

namespace App\Entity;

use App\Repository\ProdigiProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProdigiProductRepository::class)]
#[ORM\Table(name: 'prodigi_product')]
#[ORM\Index(name: 'idx_sku', columns: ['sku'])]
#[ORM\Index(name: 'idx_category', columns: ['category'])]
#[ORM\Index(name: 'idx_last_updated', columns: ['last_updated_at'])]
class ProdigiProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $sku;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $category;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $basePrice;

    #[ORM\Column(type: 'string', length: 100)]
    private string $paperType;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $dimensions = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $attributes = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiData = [];

    #[ORM\Column(type: 'boolean')]
    private bool $isAvailable = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastUpdatedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lastUpdatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): self
    {
        $this->sku = $sku;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function setBasePrice(float $basePrice): self
    {
        $this->basePrice = $basePrice;
        return $this;
    }

    public function getPaperType(): string
    {
        return $this->paperType;
    }

    public function setPaperType(string $paperType): self
    {
        $this->paperType = $paperType;
        return $this;
    }

    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    public function setDimensions(?array $dimensions): self
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    public function setAttributes(?array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function getApiData(): ?array
    {
        return $this->apiData;
    }

    public function setApiData(?array $apiData): self
    {
        $this->apiData = $apiData;
        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): self
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }

    public function getLastUpdatedAt(): \DateTimeImmutable
    {
        return $this->lastUpdatedAt;
    }

    public function setLastUpdatedAt(\DateTimeImmutable $lastUpdatedAt): self
    {
        $this->lastUpdatedAt = $lastUpdatedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Met à jour le timestamp de dernière modification
     */
    public function touch(): self
    {
        $this->lastUpdatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Vérifie si le produit doit être rafraîchi (plus de 24h)
     */
    public function needsRefresh(int $maxAgeHours = 24): bool
    {
        $maxAge = new \DateTimeImmutable("-{$maxAgeHours} hours");
        return $this->lastUpdatedAt < $maxAge;
    }
}
