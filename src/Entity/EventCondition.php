<?php

namespace App\Entity;

use App\Repository\EventConditionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventConditionRepository::class)]
class EventCondition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'conditions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Column(length: 100)]
    private ?string $entityClass = null;

    #[ORM\Column(length: 100)]
    private ?string $attributeName = null;

    #[ORM\Column(length: 20)]
    private ?string $operator = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $value = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isActive = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): static
    {
        $this->entityClass = $entityClass;
        return $this;
    }

    public function getAttributeName(): ?string
    {
        return $this->attributeName;
    }

    public function setAttributeName(string $attributeName): static
    {
        $this->attributeName = $attributeName;
        return $this;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): static
    {
        $this->operator = $operator;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Vérifie si l'utilisateur respecte cette condition (méthode legacy)
     * @deprecated Utiliser checkEntityCondition() à la place
     */
    public function checkCondition($user): bool
    {
        return $this->checkEntityCondition($user);
    }

    private function checkUserAttribute($user): bool
    {
        $value = $this->getUserAttributeValue($user);
        $requiredValue = $this->value;
        
        return match($this->operator) {
            '=' => $value == $requiredValue,
            '!=' => $value != $requiredValue,
            '>' => $value > $requiredValue,
            '>=' => $value >= $requiredValue,
            '<' => $value < $requiredValue,
            '<=' => $value <= $requiredValue,
            'contains' => str_contains($value ?? '', $requiredValue),
            'not_contains' => !str_contains($value ?? '', $requiredValue),
            'in' => in_array($value, explode(',', $requiredValue)),
            'not_in' => !in_array($value, explode(',', $requiredValue)),
            default => true
        };
    }

    private function getUserAttributeValue($user)
    {
        $attribute = $this->attributeName;

        // Propriétés directes de User
        $directProperties = [
            'firstName' => fn($u) => $u->getFirstName(),
            'lastName' => fn($u) => $u->getLastName(),
            'email' => fn($u) => $u->getEmail(),
            'status' => fn($u) => $u->getStatus(),
            'active' => fn($u) => $u->isActive(),
            'emailVerified' => fn($u) => $u->isEmailVerified(),
        ];

        if (isset($directProperties[$attribute])) {
            return $directProperties[$attribute]($user);
        }

        // Tenter d'accéder via getter
        $methodName = 'get' . ucfirst($attribute);
        if (method_exists($user, $methodName)) {
            return $user->$methodName();
        }

        return null;
    }

    public function getDisplayName(): string
    {
        $entityName = class_basename($this->entityClass);
        return "{$entityName}.{$this->attributeName} {$this->operator} {$this->value}";
    }
    
    /**
     * Vérifie si l'entité donnée respecte cette condition
     */
    public function checkEntityCondition($entity): bool
    {
        if (!$entity || get_class($entity) !== $this->entityClass) {
            return false;
        }
        
        $value = $this->getEntityAttributeValue($entity);
        $requiredValue = $this->value;
        
        return match($this->operator) {
            '=' => $value == $requiredValue,
            '!=' => $value != $requiredValue,
            '>' => is_numeric($value) && is_numeric($requiredValue) && $value > $requiredValue,
            '>=' => is_numeric($value) && is_numeric($requiredValue) && $value >= $requiredValue,
            '<' => is_numeric($value) && is_numeric($requiredValue) && $value < $requiredValue,
            '<=' => is_numeric($value) && is_numeric($requiredValue) && $value <= $requiredValue,
            'contains' => str_contains($value ?? '', $requiredValue),
            'not_contains' => !str_contains($value ?? '', $requiredValue),
            'in' => in_array($value, explode(',', $requiredValue)),
            'not_in' => !in_array($value, explode(',', $requiredValue)),
            'exists' => !empty($value),
            'not_exists' => empty($value),
            default => true
        };
    }
    
    /**
     * Récupère la valeur d'un attribut sur n'importe quelle entité
     */
    private function getEntityAttributeValue($entity)
    {
        $attribute = $this->attributeName;

        // D'abord essayer les propriétés directes via les getters
        $methodName = 'get' . ucfirst($attribute);
        if (method_exists($entity, $methodName)) {
            return $entity->$methodName();
        }

        // Essayer les méthodes is/has/can pour les booléens
        $isMethodName = 'is' . ucfirst($attribute);
        if (method_exists($entity, $isMethodName)) {
            return $entity->$isMethodName();
        }

        $hasMethodName = 'has' . ucfirst($attribute);
        if (method_exists($entity, $hasMethodName)) {
            return $entity->$hasMethodName();
        }

        // Support pour les méthodes canXxx (ex: canRegisterToEvents)
        if (method_exists($entity, $attribute)) {
            return $entity->$attribute();
        }

        return null;
    }
}