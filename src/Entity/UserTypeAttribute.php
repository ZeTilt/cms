<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_type_attributes')]
#[ORM\UniqueConstraint(name: 'unique_user_type_attribute', columns: ['user_type_id', 'attribute_key'])]
class UserTypeAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserType::class, inversedBy: 'attributes')]
    #[ORM\JoinColumn(nullable: false)]
    private UserType $userType;

    #[ORM\Column(length: 100)]
    private string $attributeKey;

    #[ORM\Column(length: 150)]
    private string $displayName;

    #[ORM\Column(length: 50)]
    private string $attributeType = 'text'; // text, number, boolean, date, json, select, textarea

    #[ORM\Column(type: 'boolean')]
    private bool $required = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $defaultValue = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $validationRules = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $options = null; // For select type: list of options

    #[ORM\Column]
    private int $displayOrder = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

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

    public function getUserType(): UserType
    {
        return $this->userType;
    }

    public function setUserType(UserType $userType): static
    {
        $this->userType = $userType;
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

    public function getAttributeType(): string
    {
        return $this->attributeType;
    }

    public function setAttributeType(string $attributeType): static
    {
        $this->attributeType = $attributeType;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): static
    {
        $this->required = $required;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): static
    {
        $this->defaultValue = $defaultValue;
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

    public function getValidationRules(): ?array
    {
        return $this->validationRules;
    }

    public function setValidationRules(?array $validationRules): static
    {
        $this->validationRules = $validationRules;
        return $this;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Get HTML input type for forms
     */
    public function getHtmlInputType(): string
    {
        return match ($this->attributeType) {
            'number' => 'number',
            'date' => 'datetime-local',
            'boolean' => 'checkbox',
            'textarea' => 'textarea',
            'select' => 'select',
            default => 'text',
        };
    }

    /**
     * Get validation attributes for HTML forms
     */
    public function getHtmlValidationAttributes(): array
    {
        $attributes = [];

        if ($this->required) {
            $attributes['required'] = true;
        }

        if ($this->validationRules) {
            if (isset($this->validationRules['min_length'])) {
                $attributes['minlength'] = $this->validationRules['min_length'];
            }
            if (isset($this->validationRules['max_length'])) {
                $attributes['maxlength'] = $this->validationRules['max_length'];
            }
            if (isset($this->validationRules['pattern'])) {
                $attributes['pattern'] = $this->validationRules['pattern'];
            }
            if (isset($this->validationRules['min'])) {
                $attributes['min'] = $this->validationRules['min'];
            }
            if (isset($this->validationRules['max'])) {
                $attributes['max'] = $this->validationRules['max'];
            }
        }

        return $attributes;
    }

    /**
     * Validate a value against this attribute's rules
     */
    public function validateValue(mixed $value): array
    {
        $errors = [];

        // Required validation
        if ($this->required && (empty($value) && $value !== '0' && $value !== 0)) {
            $errors[] = sprintf('%s is required', $this->displayName);
        }

        if ($value !== null && $value !== '' && $this->validationRules) {
            // Type-specific validation
            switch ($this->attributeType) {
                case 'number':
                    if (!is_numeric($value)) {
                        $errors[] = sprintf('%s must be a number', $this->displayName);
                    } else {
                        if (isset($this->validationRules['min']) && $value < $this->validationRules['min']) {
                            $errors[] = sprintf('%s must be at least %s', $this->displayName, $this->validationRules['min']);
                        }
                        if (isset($this->validationRules['max']) && $value > $this->validationRules['max']) {
                            $errors[] = sprintf('%s must be at most %s', $this->displayName, $this->validationRules['max']);
                        }
                    }
                    break;

                case 'text':
                case 'textarea':
                    if (isset($this->validationRules['min_length']) && strlen($value) < $this->validationRules['min_length']) {
                        $errors[] = sprintf('%s must be at least %d characters', $this->displayName, $this->validationRules['min_length']);
                    }
                    if (isset($this->validationRules['max_length']) && strlen($value) > $this->validationRules['max_length']) {
                        $errors[] = sprintf('%s must be at most %d characters', $this->displayName, $this->validationRules['max_length']);
                    }
                    if (isset($this->validationRules['pattern']) && !preg_match($this->validationRules['pattern'], $value)) {
                        $errors[] = sprintf('%s format is invalid', $this->displayName);
                    }
                    break;

                case 'select':
                    if ($this->options && !in_array($value, array_column($this->options, 'value'))) {
                        $errors[] = sprintf('%s has an invalid value', $this->displayName);
                    }
                    break;
            }
        }

        return $errors;
    }

    /**
     * Get typed value based on attribute type
     */
    public function getTypedValue(string $rawValue): mixed
    {
        return match ($this->attributeType) {
            'boolean' => filter_var($rawValue, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($rawValue) ? (float) $rawValue : null,
            'date' => $rawValue ? new \DateTime($rawValue) : null,
            'json' => $rawValue ? json_decode($rawValue, true) : null,
            default => $rawValue,
        };
    }

    public function __toString(): string
    {
        return $this->displayName;
    }
}