<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'entity_attributes')]
#[ORM\Index(columns: ['entity_type', 'entity_id'], name: 'idx_entity_lookup')]
class EntityAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $entityType;

    #[ORM\Column]
    private int $entityId;

    #[ORM\Column(length: 100)]
    private string $attributeName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $attributeValue = null;

    #[ORM\Column(length: 20)]
    private string $attributeType = 'text';

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

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): static
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getAttributeName(): string
    {
        return $this->attributeName;
    }

    public function setAttributeName(string $attributeName): static
    {
        $this->attributeName = $attributeName;
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
     * Obtient la valeur typée selon le type d'attribut
     */
    public function getTypedValue(): mixed
    {
        return match($this->attributeType) {
            'boolean' => filter_var($this->attributeValue, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->attributeValue,
            'float' => (float) $this->attributeValue,
            'date' => $this->attributeValue ? new \DateTime($this->attributeValue) : null,
            'datetime' => $this->attributeValue ? new \DateTime($this->attributeValue) : null,
            'json' => $this->attributeValue ? json_decode($this->attributeValue, true) : null,
            'file' => $this->attributeValue, // Chemin vers le fichier
            'select' => $this->attributeValue,
            default => $this->attributeValue // text par défaut
        };
    }

    /**
     * Définit la valeur en la convertissant selon le type
     */
    public function setTypedValue(mixed $value): static
    {
        $this->attributeValue = match($this->attributeType) {
            'boolean' => $value ? '1' : '0',
            'integer', 'float' => (string) $value,
            'date' => $value instanceof \DateTime ? $value->format('Y-m-d') : $value,
            'datetime' => $value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : $value,
            'json' => is_array($value) || is_object($value) ? json_encode($value) : $value,
            default => (string) $value
        };
        
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Vérifie si l'attribut est un fichier
     */
    public function isFileType(): bool
    {
        return $this->attributeType === 'file';
    }

    /**
     * Vérifie si l'attribut est une liste de sélection
     */
    public function isSelectType(): bool
    {
        return $this->attributeType === 'select';
    }
}