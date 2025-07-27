<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'system_settings')]
class SystemSetting
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 100)]
    private string $settingKey;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $settingValue = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $settingType = 'string';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getSettingKey(): string
    {
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): self
    {
        $this->settingKey = $settingKey;
        return $this;
    }

    public function getSettingValue(): ?string
    {
        return $this->settingValue;
    }

    public function setSettingValue(?string $settingValue): self
    {
        $this->settingValue = $settingValue;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getSettingType(): ?string
    {
        return $this->settingType;
    }

    public function setSettingType(?string $settingType): self
    {
        $this->settingType = $settingType;
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

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Get the parsed value based on setting type
     */
    public function getParsedValue()
    {
        if ($this->settingValue === null) {
            return null;
        }

        return match ($this->settingType) {
            'array', 'json' => json_decode($this->settingValue, true),
            'boolean' => filter_var($this->settingValue, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->settingValue,
            'float' => (float) $this->settingValue,
            default => $this->settingValue,
        };
    }

    /**
     * Set value with automatic type detection and serialization
     */
    public function setValue(mixed $value): self
    {
        if (is_array($value)) {
            $this->settingType = 'array';
            $this->settingValue = json_encode($value);
        } elseif (is_bool($value)) {
            $this->settingType = 'boolean';
            $this->settingValue = $value ? '1' : '0';
        } elseif (is_int($value)) {
            $this->settingType = 'integer';
            $this->settingValue = (string) $value;
        } elseif (is_float($value)) {
            $this->settingType = 'float';
            $this->settingValue = (string) $value;
        } else {
            $this->settingType = 'string';
            $this->settingValue = (string) $value;
        }

        $this->updatedAt = new \DateTime();
        return $this;
    }
}