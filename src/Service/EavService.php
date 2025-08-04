<?php

namespace App\Service;

use App\Entity\EntityAttribute;
use App\Repository\EntityAttributeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EavService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EntityAttributeRepository $attributeRepository,
        private string $uploadDirectory = 'uploads/attributes'
    ) {}

    /**
     * Définit un attribut pour une entité
     */
    public function setAttribute(string $entityType, int $entityId, string $attributeName, mixed $value, string $type = 'text'): EntityAttribute
    {
        $attribute = $this->attributeRepository->findOneByEntityAndAttribute($entityType, $entityId, $attributeName);
        
        if (!$attribute) {
            $attribute = new EntityAttribute();
            $attribute->setEntityType($entityType)
                     ->setEntityId($entityId)
                     ->setAttributeName($attributeName)
                     ->setAttributeType($type);
        }

        $attribute->setTypedValue($value);
        
        $this->entityManager->persist($attribute);
        $this->entityManager->flush();
        
        return $attribute;
    }

    /**
     * Obtient la valeur d'un attribut
     */
    public function getAttribute(string $entityType, int $entityId, string $attributeName, mixed $defaultValue = null): mixed
    {
        $attribute = $this->attributeRepository->findOneByEntityAndAttribute($entityType, $entityId, $attributeName);
        
        return $attribute ? $attribute->getTypedValue() : $defaultValue;
    }

    /**
     * Obtient tous les attributs d'une entité sous forme de tableau
     */
    public function getEntityAttributes(string $entityType, int $entityId): array
    {
        $attributes = $this->attributeRepository->findByEntity($entityType, $entityId);
        $result = [];
        
        foreach ($attributes as $attribute) {
            $result[$attribute->getAttributeName()] = $attribute->getTypedValue();
        }
        
        return $result;
    }

    /**
     * Obtient tous les objets AttributeEntity d'une entité
     */
    public function getEntityAttributeObjects(string $entityType, int $entityId): array
    {
        return $this->attributeRepository->findByEntity($entityType, $entityId);
    }

    /**
     * Obtient tous les attributs d'une entité avec les valeurs brutes (string)
     * Utile pour l'affichage administratif
     */
    public function getEntityAttributesRaw(string $entityType, int $entityId): array
    {
        $attributes = $this->attributeRepository->findByEntity($entityType, $entityId);
        $result = [];
        
        foreach ($attributes as $attribute) {
            $result[$attribute->getAttributeName()] = $attribute->getAttributeValue();
        }
        
        return $result;
    }

    /**
     * Supprime un attribut spécifique
     */
    public function removeAttribute(string $entityType, int $entityId, string $attributeName): bool
    {
        $attribute = $this->attributeRepository->findOneByEntityAndAttribute($entityType, $entityId, $attributeName);
        
        if ($attribute) {
            // Si c'est un fichier, le supprimer du système de fichiers
            if ($attribute->isFileType() && $attribute->getAttributeValue()) {
                $this->deleteFile($attribute->getAttributeValue());
            }
            
            $this->entityManager->remove($attribute);
            $this->entityManager->flush();
            return true;
        }
        
        return false;
    }

    /**
     * Supprime tous les attributs d'une entité
     */
    public function removeEntityAttributes(string $entityType, int $entityId): int
    {
        // Supprimer les fichiers associés
        $attributes = $this->attributeRepository->findByEntity($entityType, $entityId);
        foreach ($attributes as $attribute) {
            if ($attribute->isFileType() && $attribute->getAttributeValue()) {
                $this->deleteFile($attribute->getAttributeValue());
            }
        }
        
        return $this->attributeRepository->deleteByEntity($entityType, $entityId);
    }

    /**
     * Gère l'upload d'un fichier pour un attribut
     */
    public function setFileAttribute(string $entityType, int $entityId, string $attributeName, UploadedFile $file): EntityAttribute
    {
        // Supprimer l'ancien fichier s'il existe
        $existingAttribute = $this->attributeRepository->findOneByEntityAndAttribute($entityType, $entityId, $attributeName);
        if ($existingAttribute && $existingAttribute->getAttributeValue()) {
            $this->deleteFile($existingAttribute->getAttributeValue());
        }

        // Générer un nom de fichier unique
        $filename = uniqid() . '_' . $file->getClientOriginalName();
        $relativePath = $this->uploadDirectory . '/' . $entityType . '/' . $filename;
        
        // Créer le dossier s'il n'existe pas
        $fullDirectory = 'public/' . $this->uploadDirectory . '/' . $entityType;
        if (!is_dir($fullDirectory)) {
            mkdir($fullDirectory, 0755, true);
        }
        
        // Déplacer le fichier
        $file->move($fullDirectory, $filename);
        
        // Enregistrer l'attribut
        return $this->setAttribute($entityType, $entityId, $attributeName, $relativePath, 'file');
    }

    /**
     * Définit plusieurs attributs en une fois
     */
    public function setMultipleAttributes(string $entityType, int $entityId, array $attributes): void
    {
        foreach ($attributes as $name => $data) {
            if (is_array($data) && isset($data['value'], $data['type'])) {
                $this->setAttribute($entityType, $entityId, $name, $data['value'], $data['type']);
            } else {
                $this->setAttribute($entityType, $entityId, $name, $data);
            }
        }
    }

    /**
     * Recherche des entités par attribut
     */
    public function findEntitiesByAttribute(string $entityType, string $attributeName, mixed $value): array
    {
        $result = $this->attributeRepository->findEntitiesByAttributeValue($entityType, $attributeName, (string) $value);
        return array_column($result, 'entityId');
    }

    /**
     * Obtient les définitions d'attributs disponibles pour un type d'entité
     */
    public function getAttributeDefinitions(string $entityType): array
    {
        // Cette méthode peut être étendue pour inclure des définitions d'attributs
        // stockées en base de données ou dans la configuration
        return match($entityType) {
            'User' => [
                'medical_certificate' => ['type' => 'file', 'label' => 'Certificat médical', 'required' => false],
                'diving_level' => ['type' => 'select', 'label' => 'Niveau de plongée', 'required' => false, 'options' => ['N1', 'N2', 'N3', 'N4', 'MF1', 'MF2']],
                'emergency_contact' => ['type' => 'text', 'label' => 'Contact d\'urgence', 'required' => false],
                'phone_number' => ['type' => 'text', 'label' => 'Numéro de téléphone', 'required' => false],
                'birth_date' => ['type' => 'date', 'label' => 'Date de naissance', 'required' => false],
                'is_instructor' => ['type' => 'boolean', 'label' => 'Instructeur', 'required' => false],
            ],
            'Event' => [
                'max_participants' => ['type' => 'integer', 'label' => 'Nombre max de participants', 'required' => false],
                'difficulty_level' => ['type' => 'select', 'label' => 'Niveau de difficulté', 'required' => false, 'options' => ['Débutant', 'Intermédiaire', 'Avancé']],
                'equipment_required' => ['type' => 'json', 'label' => 'Équipement requis', 'required' => false],
                'meeting_point' => ['type' => 'text', 'label' => 'Point de rendez-vous', 'required' => false],
                'price' => ['type' => 'float', 'label' => 'Prix', 'required' => false],
            ],
            default => []
        };
    }

    /**
     * Supprime un fichier du système de fichiers
     */
    private function deleteFile(string $filePath): void
    {
        $fullPath = 'public/' . $filePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * Obtient les statistiques d'utilisation des attributs
     */
    public function getAttributeStats(string $entityType): array
    {
        return $this->attributeRepository->getAttributeStats($entityType);
    }

    /**
     * Obtient tous les noms d'attributs utilisés pour un type d'entité
     */
    public function getUsedAttributeNames(string $entityType): array
    {
        return $this->attributeRepository->getAttributeNames($entityType);
    }

    /**
     * Résout une référence d'entité pour l'affichage
     */
    public function resolveEntityReference(string $targetEntity, int $entityId, string $displayAttribute): ?string
    {
        try {
            // Obtenir l'entité cible
            $entityClass = 'App\\Entity\\' . $targetEntity;
            if (!class_exists($entityClass)) {
                return null;
            }

            $repository = $this->entityManager->getRepository($entityClass);
            $entity = $repository->find($entityId);
            
            if (!$entity) {
                return null;
            }

            // Résoudre l'attribut d'affichage
            return $this->resolveDisplayValue($entity, $displayAttribute);
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Résout la valeur d'affichage d'une entité
     */
    private function resolveDisplayValue(object $entity, string $attribute): ?string
    {
        // Essayer d'abord les méthodes getter standard
        $getter = 'get' . ucfirst($attribute);
        if (method_exists($entity, $getter)) {
            $value = $entity->$getter();
            return $value ? (string) $value : null;
        }

        // Essayer les propriétés publiques
        if (property_exists($entity, $attribute)) {
            $value = $entity->$attribute;
            return $value ? (string) $value : null;
        }

        // Si c'est un attribut EAV
        if (method_exists($entity, 'getDynamicAttribute')) {
            $value = $entity->getDynamicAttribute($attribute);
            return $value ? (string) $value : null;
        }

        return null;
    }

    /**
     * Obtient les options pour un attribut de type entity_reference
     */
    public function getEntityReferenceOptions(string $targetEntity, string $targetAttribute, string $displayAttribute): array
    {
        try {
            $entityClass = 'App\\Entity\\' . $targetEntity;
            if (!class_exists($entityClass)) {
                return [];
            }

            $repository = $this->entityManager->getRepository($entityClass);
            $entities = $repository->findAll();
            
            $options = [];
            foreach ($entities as $entity) {
                $id = $entity->getId();
                $targetValue = $this->resolveDisplayValue($entity, $targetAttribute);
                $displayValue = $this->resolveDisplayValue($entity, $displayAttribute);
                
                if ($targetValue !== null) {
                    $options[$targetValue] = $displayValue ?: "#{$id}";
                }
            }

            return $options;
            
        } catch (\Exception $e) {
            return [];
        }
    }
}