<?php

namespace App\Service;

use App\Entity\AttributeDefinition;
use Doctrine\ORM\EntityManagerInterface;

class AttributeDefinitionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getDefinitionsForEntity(string $entityType): array
    {
        return $this->entityManager->getRepository(AttributeDefinition::class)
            ->findBy(
                ['entityType' => $entityType, 'active' => true],
                ['displayOrder' => 'ASC', 'displayName' => 'ASC']
            );
    }

    public function getDefinition(string $entityType, string $attributeName): ?AttributeDefinition
    {
        return $this->entityManager->getRepository(AttributeDefinition::class)
            ->findOneBy([
                'entityType' => $entityType,
                'attributeName' => $attributeName,
                'active' => true
            ]);
    }

    public function createDefinition(
        string $entityType,
        string $attributeName,
        string $displayName,
        string $attributeType = 'text',
        bool $required = false,
        ?string $defaultValue = null,
        ?string $description = null,
        ?array $options = null,
        ?array $validationRules = null,
        int $displayOrder = 0
    ): AttributeDefinition {
        // Check if definition already exists
        $existing = $this->entityManager->getRepository(AttributeDefinition::class)
            ->findOneBy([
                'entityType' => $entityType,
                'attributeName' => $attributeName
            ]);

        if ($existing) {
            throw new \InvalidArgumentException(
                "Attribute definition '{$attributeName}' already exists for entity type '{$entityType}'"
            );
        }

        $definition = new AttributeDefinition();
        $definition->setEntityType($entityType)
            ->setAttributeName($attributeName)
            ->setDisplayName($displayName)
            ->setAttributeType($attributeType)
            ->setRequired($required)
            ->setDefaultValue($defaultValue)
            ->setDescription($description)
            ->setOptions($options)
            ->setValidationRules($validationRules)
            ->setDisplayOrder($displayOrder);

        $this->entityManager->persist($definition);
        
        return $definition;
    }

    public function updateDefinition(AttributeDefinition $definition): void
    {
        $this->entityManager->persist($definition);
    }

    public function deleteDefinition(AttributeDefinition $definition): void
    {
        $definition->setActive(false);
        $this->entityManager->persist($definition);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function getSupportedEntityTypes(): array
    {
        return [
            'User' => 'Utilisateurs',
            'Event' => 'Événements', 
            'Gallery' => 'Galeries',
            'Article' => 'Articles',
            'Service' => 'Services',
            'Page' => 'Pages'
        ];
    }

    public function getSupportedAttributeTypes(): array
    {
        return [
            'text' => [
                'label' => 'Texte',
                'description' => 'Texte court (une ligne)',
                'supports_validation' => ['min_length', 'max_length', 'pattern']
            ],
            'textarea' => [
                'label' => 'Zone de texte',
                'description' => 'Texte long (multilignes)',
                'supports_validation' => ['min_length', 'max_length']
            ],
            'number' => [
                'label' => 'Nombre',
                'description' => 'Nombre entier ou décimal',
                'supports_validation' => ['min_value', 'max_value']
            ],
            'boolean' => [
                'label' => 'Booléen',
                'description' => 'Oui/Non (case à cocher)',
                'supports_validation' => []
            ],
            'date' => [
                'label' => 'Date',
                'description' => 'Date (sélecteur de date)',
                'supports_validation' => ['min_date', 'max_date']
            ],
            'select' => [
                'label' => 'Liste déroulante',
                'description' => 'Choix parmi des options prédéfinies',
                'supports_validation' => [],
                'requires_options' => true
            ],
            'file' => [
                'label' => 'Fichier',
                'description' => 'Upload de fichier (PDF, images, etc.)',
                'supports_validation' => ['max_size', 'allowed_extensions']
            ],
            'json' => [
                'label' => 'Données structurées',
                'description' => 'Données complexes (JSON)',
                'supports_validation' => []
            ],
            'entity_reference' => [
                'label' => 'Référence d\'entité',
                'description' => 'Référence vers l\'attribut d\'une autre entité',
                'supports_validation' => [],
                'requires_options' => true,
                'special_config' => [
                    'target_entity' => 'string',    // Type d'entité cible (User, Event, etc.)
                    'target_attribute' => 'string', // Nom de l'attribut cible 
                    'display_attribute' => 'string' // Attribut à afficher (ex: fullName, title)
                ]
            ]
        ];
    }

    /**
     * Obtient les attributs disponibles pour une entité donnée (y compris les propriétés standard)
     */
    public function getAvailableAttributesForEntity(string $entityType): array
    {
        $attributes = [];
        
        // Propriétés standard selon le type d'entité
        $standardProperties = $this->getStandardPropertiesForEntity($entityType);
        foreach ($standardProperties as $property => $label) {
            $attributes[$property] = [
                'name' => $property,
                'label' => $label,
                'type' => 'standard'
            ];
        }
        
        // Attributs EAV définis
        $definitions = $this->getDefinitionsForEntity($entityType);
        foreach ($definitions as $definition) {
            $attributes[$definition->getAttributeName()] = [
                'name' => $definition->getAttributeName(),
                'label' => $definition->getDisplayName(),
                'type' => 'eav',
                'attribute_type' => $definition->getAttributeType()
            ];
        }
        
        return $attributes;
    }

    /**
     * Obtient les propriétés standard pour un type d'entité
     */
    private function getStandardPropertiesForEntity(string $entityType): array
    {
        $properties = [
            'User' => [
                'id' => 'ID',
                'email' => 'Email',
                'firstName' => 'Prénom',
                'lastName' => 'Nom',
                'fullName' => 'Nom complet',
                'active' => 'Actif',
                'createdAt' => 'Date de création'
            ],
            'Event' => [
                'id' => 'ID',
                'title' => 'Titre',
                'description' => 'Description',
                'startDate' => 'Date de début',
                'endDate' => 'Date de fin',
                'location' => 'Lieu',
                'capacity' => 'Capacité',
                'createdAt' => 'Date de création'
            ],
            'Article' => [
                'id' => 'ID',
                'title' => 'Titre',
                'content' => 'Contenu',
                'excerpt' => 'Extrait',
                'status' => 'Statut',
                'createdAt' => 'Date de création',
                'publishedAt' => 'Date de publication'
            ],
            'Gallery' => [
                'id' => 'ID',
                'title' => 'Titre',
                'description' => 'Description',
                'createdAt' => 'Date de création'
            ],
            'Service' => [
                'id' => 'ID',
                'name' => 'Nom',
                'description' => 'Description',
                'price' => 'Prix',
                'active' => 'Actif'
            ],
            'Page' => [
                'id' => 'ID',
                'title' => 'Titre',
                'slug' => 'Slug',
                'createdAt' => 'Date de création'
            ]
        ];

        return $properties[$entityType] ?? [];
    }
}