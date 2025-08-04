<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\EventRegistration;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service pour valider les conditions d'inscription et gérer les inscriptions aux événements
 */
class EventRegistrationValidator
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Valider si un utilisateur peut s'inscrire à un événement
     */
    public function validateUserForEvent(User $user, Event $event): array
    {
        $errors = [];

        // Vérifications de base
        if (!$event->acceptsRegistrations()) {
            $errors[] = 'Les inscriptions ne sont pas ouvertes pour cet événement';
            return $errors;
        }

        if ($event->isUserRegistered($user)) {
            $errors[] = 'Vous êtes déjà inscrit à cet événement';
            return $errors;
        }

        // Validations spécifiques à la plongée
        $divingErrors = $event->canUserRegister($user);
        $errors = array_merge($errors, $divingErrors);

        return $errors;
    }

    /**
     * Créer une inscription avec validation
     */
    public function registerUserForEvent(User $user, Event $event, array $options = []): EventRegistration
    {
        $errors = $this->validateUserForEvent($user, $event);
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Inscription impossible: ' . implode(', ', $errors));
        }

        $registration = new EventRegistration();
        $registration->setUser($user);
        $registration->setEvent($event);

        // Options d'inscription
        if (isset($options['numberOfSpots'])) {
            $registration->setNumberOfSpots($options['numberOfSpots']);
        }

        if (isset($options['departureLocation'])) {
            $registration->setDepartureLocation($options['departureLocation']);
        }

        if (isset($options['comment'])) {
            $registration->setRegistrationComment($options['comment']);
        }

        // Déterminer le statut (inscrit ou liste d'attente)
        if ($event->requiresWaitingList()) {
            $registration->setStatus('waiting_list');
        } else {
            $registration->setStatus('registered');
        }

        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        return $registration;
    }

    /**
     * Obtenir les opérateurs de condition disponibles
     */
    public function getAvailableOperators(): array
    {
        return [
            'equals' => 'Égal à',
            'not_equals' => 'Différent de',
            'greater_than' => 'Supérieur à',
            'less_than' => 'Inférieur à',
            'greater_or_equal' => 'Supérieur ou égal à',
            'less_or_equal' => 'Inférieur ou égal à',
            'select_option_gte' => 'Option ≥ (minimum requis dans la liste)',
            'select_option_equals' => 'Option exacte dans la liste',
            'contains' => 'Contient',
            'exists' => 'Existe (non vide)',
            'not_exists' => 'N\'existe pas (vide)',
            'in_list' => 'Dans la liste',
            'not_in_list' => 'Pas dans la liste'
        ];
    }

    /**
     * Obtenir les entités disponibles pour les conditions
     */
    public function getAvailableEntities(): array
    {
        return [
            'User' => 'Utilisateur',
            'Event' => 'Événement',
            'Gallery' => 'Galerie'
        ];
    }

    /**
     * Obtenir les attributs disponibles pour une entité donnée
     */
    public function getAvailableAttributes(string $entityType = 'User'): array
    {
        $attributes = [];
        
        // 1. Récupérer les attributs définis dans attribute_definitions
        $attributeDefinitions = $this->entityManager->getRepository(\App\Entity\AttributeDefinition::class)
            ->findBy([
                'entityType' => $entityType,
                'active' => true
            ], ['displayOrder' => 'ASC']);
            
        foreach ($attributeDefinitions as $definition) {
            $attributes[$definition->getAttributeName()] = $definition->getDisplayName();
        }
        
        // 2. Récupérer les attributs utilisés dans entity_attributes pour cette entité
        // mais seulement ceux qui ont une définition active
        $sql = "SELECT DISTINCT ea.attribute_name FROM entity_attributes ea 
                INNER JOIN attribute_definitions ad ON ea.attribute_name = ad.attribute_name 
                AND ea.entity_type = ad.entity_type
                WHERE ea.entity_type = :entityType AND ad.active = 1";
        $result = $this->entityManager->getConnection()->executeQuery($sql, ['entityType' => $entityType]);
        
        while ($row = $result->fetchAssociative()) {
            $key = $row['attribute_name'];
            // Ajouter seulement si pas déjà défini dans attribute_definitions
            if (!isset($attributes[$key])) {
                $attributes[$key] = ucfirst(str_replace('_', ' ', $key));
            }
        }
        
        // 3. Ajouter les propriétés natives de l'entité selon le type
        $nativeAttributes = $this->getNativeEntityAttributes($entityType);
        $attributes = array_merge($nativeAttributes, $attributes);
        
        return $attributes;
    }
    
    /**
     * Obtenir les propriétés natives d'une entité
     */
    private function getNativeEntityAttributes(string $entityType): array
    {
        return match($entityType) {
            'User' => [
                'username' => 'Nom d\'utilisateur',
                'email' => 'Email',
                'firstName' => 'Prénom',
                'lastName' => 'Nom',
                'status' => 'Statut',
                'active' => 'Actif',
                'createdAt' => 'Date de création'
            ],
            'Event' => [
                'title' => 'Titre',
                'status' => 'Statut',
                'type' => 'Type',
                'startDate' => 'Date de début',
                'endDate' => 'Date de fin',
                'location' => 'Lieu',
                'maxParticipants' => 'Participants max',
                'requiresRegistration' => 'Inscription requise'
            ],
            'Gallery' => [
                'title' => 'Titre',
                'visibility' => 'Visibilité',
                'createdAt' => 'Date de création'
            ],
            default => []
        };
    }

    /**
     * Obtenir les détails d'un attribut pour une entité donnée
     */
    public function getAttributeDetails(string $entityType, string $attributeKey): array
    {
        // 1. Chercher d'abord dans attribute_definitions
        $attributeDefinition = $this->entityManager->getRepository(\App\Entity\AttributeDefinition::class)
            ->findOneBy([
                'entityType' => $entityType,
                'attributeName' => $attributeKey,
                'active' => true
            ]);
            
        if ($attributeDefinition) {
            return [
                'type' => $attributeDefinition->getAttributeType(),
                'options' => $attributeDefinition->getOptions() ?? [],
                'required' => $attributeDefinition->isRequired(),
                'default_value' => $attributeDefinition->getDefaultValue(),
                'description' => $attributeDefinition->getDescription()
            ];
        }
        
        // 2. Si c'est un attribut natif de l'entité, déterminer son type
        $nativeType = $this->getNativeAttributeType($entityType, $attributeKey);
        if ($nativeType) {
            return $nativeType;
        }
        
        // 3. Pour les User, chercher dans les UserTypes (legacy)
        if ($entityType === 'User') {
            $userTypes = $this->entityManager->getRepository(\App\Entity\UserType::class)->findAll();
            
            foreach ($userTypes as $userType) {
                $attributes = $userType->getAttributes() ?? [];
                if (isset($attributes[$attributeKey])) {
                    return $attributes[$attributeKey];
                }
            }
        }
        
        // 4. Chercher dans entity_attributes pour déterminer le type
        $sql = "SELECT attribute_type, attribute_value FROM entity_attributes 
                WHERE entity_type = :entityType AND attribute_name = :key 
                LIMIT 10";
        $result = $this->entityManager->getConnection()->executeQuery($sql, [
            'entityType' => $entityType,
            'key' => $attributeKey
        ]);
        
        $values = [];
        $type = 'text';
        
        while ($row = $result->fetchAssociative()) {
            if ($row['attribute_type']) {
                $type = $row['attribute_type'];
            }
            if ($row['attribute_value'] && !in_array($row['attribute_value'], $values)) {
                $values[] = $row['attribute_value'];
            }
        }
        
        return [
            'type' => $type,
            'options' => $type === 'select' ? $values : [],
            'required' => false
        ];
    }
    
    /**
     * Obtenir le type d'un attribut natif d'une entité
     */
    private function getNativeAttributeType(string $entityType, string $attributeKey): ?array
    {
        $nativeTypes = match($entityType) {
            'User' => [
                'username' => ['type' => 'text', 'required' => true],
                'email' => ['type' => 'email', 'required' => true],
                'firstName' => ['type' => 'text', 'required' => false],
                'lastName' => ['type' => 'text', 'required' => false],
                'status' => ['type' => 'select', 'options' => ['pending_approval', 'approved', 'rejected', 'suspended'], 'required' => true],
                'active' => ['type' => 'boolean', 'required' => true],
                'createdAt' => ['type' => 'datetime', 'required' => true]
            ],
            'Event' => [
                'title' => ['type' => 'text', 'required' => true],
                'status' => ['type' => 'select', 'options' => ['draft', 'published', 'cancelled'], 'required' => true],
                'type' => ['type' => 'select', 'options' => ['event', 'meeting', 'conference', 'workshop'], 'required' => true],
                'startDate' => ['type' => 'datetime', 'required' => false],
                'endDate' => ['type' => 'datetime', 'required' => false],
                'location' => ['type' => 'text', 'required' => false],
                'maxParticipants' => ['type' => 'integer', 'required' => false],
                'requiresRegistration' => ['type' => 'boolean', 'required' => true]
            ],
            'Gallery' => [
                'title' => ['type' => 'text', 'required' => true],
                'visibility' => ['type' => 'select', 'options' => ['public', 'private'], 'required' => true],
                'createdAt' => ['type' => 'datetime', 'required' => true]
            ],
            default => []
        };
        
        if (isset($nativeTypes[$attributeKey])) {
            $details = $nativeTypes[$attributeKey];
            return [
                'type' => $details['type'],
                'options' => $details['options'] ?? [],
                'required' => $details['required'] ?? false
            ];
        }
        
        return null;
    }

    /**
     * Obtenir toutes les définitions d'attributs avec leurs détails pour toutes les entités
     */
    public function getAttributesWithDetails(): array
    {
        $entities = $this->getAvailableEntities();
        $detailed = [];
        
        foreach ($entities as $entityType => $entityLabel) {
            $attributes = $this->getAvailableAttributes($entityType);
            $detailed[$entityType] = [
                'label' => $entityLabel,
                'attributes' => []
            ];
            
            foreach ($attributes as $key => $label) {
                $detailed[$entityType]['attributes'][$key] = [
                    'label' => $label,
                    'details' => $this->getAttributeDetails($entityType, $key)
                ];
            }
        }
        
        return $detailed;
    }

    /**
     * Obtenir les événements recommandés pour un utilisateur
     */
    public function getRecommendedEventsForUser(User $user): array
    {
        $qb = $this->entityManager->getRepository(Event::class)->createQueryBuilder('e');
        
        $qb->where('e.status = :status')
           ->andWhere('e.startDate > :now')
           ->andWhere('e.requiresRegistration = true')
           ->setParameter('status', 'published')
           ->setParameter('now', new \DateTimeImmutable())
           ->orderBy('e.startDate', 'ASC');

        $allEvents = $qb->getQuery()->getResult();
        $recommendedEvents = [];

        foreach ($allEvents as $event) {
            $errors = $event->canUserRegister($user);
            
            // Si aucune erreur, recommander l'événement
            if (empty($errors)) {
                $recommendedEvents[] = $event;
            }
        }

        return $recommendedEvents;
    }

    /**
     * Créer un exemple de condition d'inscription pour test
     */
    public function createSampleCondition(Event $event): void
    {
        // Exemple: Niveau de plongée N2 minimum
        $event->addRegistrationCondition(
            'niveau_plongee',
            'in_list', 
            ['N2', 'N3', 'N4', 'MF1', 'MF2'],
            'Niveau de plongée N2 minimum requis'
        );

        // Exemple: Certificat médical requis
        $event->addRegistrationCondition(
            'certificat_medical',
            'exists',
            true,
            'Certificat médical valide requis'
        );
    }
}