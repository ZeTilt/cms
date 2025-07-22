<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\EntityAttribute;

class AttributeManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function setAttribute(object $entity, string $attributeName, mixed $value, string $type = 'text'): void
    {
        $entityType = $this->getEntityType($entity);
        $entityId = $entity->getId();

        if (!$entityId) {
            throw new \InvalidArgumentException('Entity must be persisted before setting attributes');
        }

        // Check if attribute already exists
        $attribute = $this->entityManager->getRepository(EntityAttribute::class)
            ->findOneBy([
                'entityType' => $entityType,
                'entityId' => $entityId,
                'attributeName' => $attributeName
            ]);

        if (!$attribute) {
            $attribute = new EntityAttribute();
            $attribute->setEntityType($entityType);
            $attribute->setEntityId($entityId);
            $attribute->setAttributeName($attributeName);
            $attribute->setAttributeType($type);
        }

        $attribute->setAttributeValue($this->serializeValue($value, $type));

        $this->entityManager->persist($attribute);
    }

    public function getAttribute(object $entity, string $attributeName): mixed
    {
        $entityType = $this->getEntityType($entity);
        $entityId = $entity->getId();

        if (!$entityId) {
            return null;
        }

        $attribute = $this->entityManager->getRepository(EntityAttribute::class)
            ->findOneBy([
                'entityType' => $entityType,
                'entityId' => $entityId,
                'attributeName' => $attributeName
            ]);

        if (!$attribute) {
            return null;
        }

        return $this->deserializeValue($attribute->getAttributeValue(), $attribute->getAttributeType());
    }

    public function getAttributes(object $entity): array
    {
        $entityType = $this->getEntityType($entity);
        $entityId = $entity->getId();

        if (!$entityId) {
            return [];
        }

        $attributes = $this->entityManager->getRepository(EntityAttribute::class)
            ->findBy([
                'entityType' => $entityType,
                'entityId' => $entityId
            ]);

        $result = [];
        foreach ($attributes as $attribute) {
            $result[$attribute->getAttributeName()] = $this->deserializeValue(
                $attribute->getAttributeValue(),
                $attribute->getAttributeType()
            );
        }

        return $result;
    }

    public function removeAttribute(object $entity, string $attributeName): void
    {
        $entityType = $this->getEntityType($entity);
        $entityId = $entity->getId();

        if (!$entityId) {
            return;
        }

        $attribute = $this->entityManager->getRepository(EntityAttribute::class)
            ->findOneBy([
                'entityType' => $entityType,
                'entityId' => $entityId,
                'attributeName' => $attributeName
            ]);

        if ($attribute) {
            $this->entityManager->remove($attribute);
        }
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    private function getEntityType(object $entity): string
    {
        $className = get_class($entity);
        return basename(str_replace('\\', '/', $className));
    }

    private function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'json' => json_encode($value),
            'date' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : (string) $value,
            'boolean' => $value ? '1' : '0',
            'integer', 'float' => (string) $value,
            default => (string) $value
        };
    }

    private function deserializeValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'json' => json_decode($value, true),
            'date' => new \DateTime($value),
            'boolean' => $value === '1',
            'integer' => (int) $value,
            'float' => (float) $value,
            default => $value
        };
    }
}