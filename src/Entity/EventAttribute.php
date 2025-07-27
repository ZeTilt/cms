<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_attributes')]
class EventAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'eventAttributes')]
    #[ORM\JoinColumn(nullable: false)]
    private Event $event;

    #[ORM\Column(length: 100)]
    private string $attributeKey = '';

    #[ORM\Column(length: 20)]
    private string $attributeType = 'text'; // text, number, boolean, date, json, select, textarea

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $attributeValue = null;

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

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): static
    {
        $this->event = $event;
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

    public function getAttributeType(): string
    {
        return $this->attributeType;
    }

    public function setAttributeType(string $attributeType): static
    {
        $this->attributeType = $attributeType;
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
     * Get typed value based on attribute type
     */
    public function getTypedValue(): mixed
    {
        if ($this->attributeValue === null) {
            return null;
        }

        switch ($this->attributeType) {
            case 'boolean':
                return $this->attributeValue === '1' || $this->attributeValue === 'true';
            case 'number':
                return is_numeric($this->attributeValue) ? (float) $this->attributeValue : null;
            case 'json':
                return json_decode($this->attributeValue, true);
            case 'date':
                try {
                    return new \DateTimeImmutable($this->attributeValue);
                } catch (\Exception $e) {
                    return null;
                }
            default:
                return $this->attributeValue;
        }
    }

    /**
     * Set typed value based on attribute type
     */
    public function setTypedValue(mixed $value): static
    {
        if ($value === null) {
            $this->setAttributeValue(null);
            return $this;
        }

        switch ($this->attributeType) {
            case 'boolean':
                $this->setAttributeValue($value ? '1' : '0');
                break;
            case 'number':
                $this->setAttributeValue((string) $value);
                break;
            case 'json':
                $this->setAttributeValue(json_encode($value));
                break;
            case 'date':
                if ($value instanceof \DateTimeInterface) {
                    $this->setAttributeValue($value->format('Y-m-d H:i:s'));
                } else {
                    $this->setAttributeValue((string) $value);
                }
                break;
            default:
                $this->setAttributeValue((string) $value);
        }

        return $this;
    }
}