<?php

namespace App\Trait;

use App\Service\EavService;

trait HasDynamicAttributesTrait
{
    private ?EavService $eavService = null;
    private ?array $cachedAttributes = null;

    /**
     * Injection du service EAV
     */
    public function setEavService(EavService $eavService): void
    {
        $this->eavService = $eavService;
    }

    /**
     * Obtient le nom de la classe pour le système EAV
     */
    private function getEntityType(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Définit un attribut dynamique
     */
    public function setDynamicAttribute(string $name, mixed $value, string $type = 'text'): void
    {
        if ($this->eavService && $this->getId()) {
            $this->eavService->setAttribute($this->getEntityType(), $this->getId(), $name, $value, $type);
            $this->cachedAttributes = null; // Invalider le cache
        }
    }

    /**
     * Obtient un attribut dynamique
     */
    public function getDynamicAttribute(string $name, mixed $defaultValue = null): mixed
    {
        if ($this->eavService && $this->getId()) {
            return $this->eavService->getAttribute($this->getEntityType(), $this->getId(), $name, $defaultValue);
        }
        
        return $defaultValue;
    }

    /**
     * Obtient tous les attributs dynamiques
     */
    public function getDynamicAttributes(): array
    {
        if ($this->cachedAttributes === null) {
            if ($this->eavService && $this->getId()) {
                $this->cachedAttributes = $this->eavService->getEntityAttributes($this->getEntityType(), $this->getId());
            } else {
                $this->cachedAttributes = [];
            }
        }
        
        return $this->cachedAttributes;
    }

    /**
     * Vérifie si un attribut dynamique existe
     */
    public function hasDynamicAttribute(string $name): bool
    {
        $attributes = $this->getDynamicAttributes();
        return array_key_exists($name, $attributes);
    }

    /**
     * Supprime un attribut dynamique
     */
    public function removeDynamicAttribute(string $name): bool
    {
        if ($this->eavService && $this->getId()) {
            $result = $this->eavService->removeAttribute($this->getEntityType(), $this->getId(), $name);
            $this->cachedAttributes = null; // Invalider le cache
            return $result;
        }
        
        return false;
    }

    /**
     * Définit plusieurs attributs dynamiques en une fois
     */
    public function setMultipleDynamicAttributes(array $attributes): void
    {
        if ($this->eavService && $this->getId()) {
            $this->eavService->setMultipleAttributes($this->getEntityType(), $this->getId(), $attributes);
            $this->cachedAttributes = null; // Invalider le cache
        }
    }

    /**
     * Obtient les définitions d'attributs disponibles pour cette entité
     */
    public function getAvailableAttributeDefinitions(): array
    {
        if ($this->eavService) {
            return $this->eavService->getAttributeDefinitions($this->getEntityType());
        }
        
        return [];
    }

    // Note: Les méthodes magiques __get et __set ont été supprimées car elles interfèrent
    // avec les proxies Doctrine. Utilisez les méthodes getDynamicAttribute() et 
    // setDynamicAttribute() directement pour accéder aux attributs EAV.

    /**
     * Méthode magique pour vérifier l'existence d'attributs dynamiques
     */
    public function __isset(string $name): bool
    {
        return $this->hasDynamicAttribute($name);
    }
}