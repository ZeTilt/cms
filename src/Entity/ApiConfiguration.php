<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'api_configuration')]
class ApiConfiguration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $apiName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $apiKey = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $partnerId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $baseUrl = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $additionalConfig = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApiName(): string
    {
        return $this->apiName;
    }

    public function setApiName(string $apiName): self
    {
        $this->apiName = $apiName;
        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): self
    {
        $this->apiKey = $apiKey;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPartnerId(): ?string
    {
        return $this->partnerId;
    }

    public function setPartnerId(?string $partnerId): self
    {
        $this->partnerId = $partnerId;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(?string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAdditionalConfig(): ?array
    {
        return $this->additionalConfig;
    }

    public function setAdditionalConfig(?array $additionalConfig): self
    {
        $this->additionalConfig = $additionalConfig;
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

    public function isConfigured(): bool
    {
        // Pour Prodigi, seule l'API key est requise (pas de Partner ID)
        if ($this->apiName === 'prodigi') {
            return !empty($this->apiKey);
        }
        
        // Pour d'autres APIs qui pourraient nécessiter Partner ID
        return !empty($this->apiKey) && !empty($this->partnerId);
    }

    public function getMaskedApiKey(): string
    {
        if (empty($this->apiKey)) {
            return 'Non configurée';
        }
        
        if (strlen($this->apiKey) <= 8) {
            return str_repeat('*', strlen($this->apiKey));
        }
        
        return substr($this->apiKey, 0, 4) . str_repeat('*', strlen($this->apiKey) - 8) . substr($this->apiKey, -4);
    }
}