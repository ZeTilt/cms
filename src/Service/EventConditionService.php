<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventCondition;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EventConditionService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Vérifie toutes les conditions d'un événement pour un utilisateur
     */
    public function checkEventConditionsForUser(Event $event, User $user): array
    {
        $violations = [];
        
        foreach ($event->getActiveConditions() as $condition) {
            if (!$condition->checkEntityCondition($user)) {
                $violations[] = [
                    'condition' => $condition,
                    'message' => $condition->getErrorMessage() ?: $this->getDefaultErrorMessage($condition),
                    'severity' => 'error'
                ];
            }
        }
        
        return $violations;
    }

    /**
     * Vérifie si un utilisateur peut s'inscrire à un événement
     */
    public function canUserRegisterForEvent(Event $event, User $user): bool
    {
        return empty($this->checkEventConditionsForUser($event, $user));
    }

    /**
     * Retourne les entités disponibles pour créer des conditions
     */
    public function getAvailableEntities(): array
    {
        return [
            'App\\Entity\\User' => 'Utilisateur',
            'App\\Entity\\Event' => 'Événement'
        ];
    }

    /**
     * Retourne les attributs disponibles pour une entité donnée
     */
    public function getAvailableAttributesForEntity(string $entityClass): array
    {
        switch ($entityClass) {
            case 'App\\Entity\\User':
                return [
                    // Propriétés directes
                    'firstName' => 'Prénom',
                    'lastName' => 'Nom',
                    'email' => 'Email',
                    'status' => 'Statut',
                    'active' => 'Actif',
                    'emailVerified' => 'Email vérifié',

                    // CACI (Certificat médical)
                    'caciStatus' => 'Statut CACI (missing/pending/expired/valid)',
                    'canRegisterToEvents' => 'Peut s\'inscrire (CACI ok)',
                    'medicalCertificateValid' => 'CACI valide (vérifié + non expiré)',

                    // Cotisation club
                    'membershipStatus' => 'Statut cotisation (missing/expired/valid)',
                    'isMembershipValid' => 'Cotisation à jour',
                    'canParticipateToEvents' => 'Peut participer (CACI + cotisation ok)',

                    // Attributs EAV courants
                    'diving_level' => 'Niveau de plongée',
                    'birth_date' => 'Date de naissance',
                    'medical_certificate_date' => 'Date certificat médical',
                    'swimming_test_date' => 'Date test de natation',
                    'freediver' => 'Apnéiste',
                    'emergency_contact_name' => 'Contact d\'urgence (nom)',
                    'emergency_contact_phone' => 'Contact d\'urgence (téléphone)',
                    'insurance_number' => 'Numéro d\'assurance',
                ];
            
            case 'App\\Entity\\Event':
                return [
                    'title' => 'Titre',
                    'type' => 'Type',
                    'status' => 'Statut',
                    'maxParticipants' => 'Nombre max de participants',
                    'currentParticipants' => 'Participants actuels',
                    'location' => 'Lieu',
                ];
            
            default:
                return [];
        }
    }

    /**
     * Retourne les opérateurs disponibles
     */
    public function getAvailableOperators(): array
    {
        return [
            '=' => 'Égal à',
            '!=' => 'Différent de',
            '>' => 'Supérieur à',
            '>=' => 'Supérieur ou égal à',
            '<' => 'Inférieur à',
            '<=' => 'Inférieur ou égal à',
            'contains' => 'Contient',
            'not_contains' => 'Ne contient pas',
            'in' => 'Dans la liste (séparé par virgules)',
            'not_in' => 'Pas dans la liste (séparé par virgules)',
            'exists' => 'Existe (non vide)',
            'not_exists' => 'N\'existe pas (vide)',
        ];
    }

    /**
     * Valide une condition avant sauvegarde
     */
    public function validateCondition(EventCondition $condition): array
    {
        $errors = [];
        
        if (!$condition->getEntityClass()) {
            $errors[] = 'La classe d\'entité est requise';
        }
        
        if (!$condition->getAttributeName()) {
            $errors[] = 'Le nom de l\'attribut est requis';
        }
        
        if (!$condition->getOperator()) {
            $errors[] = 'L\'opérateur est requis';
        }
        
        // Vérifier que la classe d'entité existe
        if ($condition->getEntityClass() && !class_exists($condition->getEntityClass())) {
            $errors[] = 'La classe d\'entité spécifiée n\'existe pas';
        }
        
        // Vérifier l'opérateur
        if ($condition->getOperator() && !array_key_exists($condition->getOperator(), $this->getAvailableOperators())) {
            $errors[] = 'Opérateur non supporté';
        }
        
        // Vérifier que la valeur est fournie pour les opérateurs qui en ont besoin
        $operatorsRequiringValue = ['=', '!=', '>', '>=', '<', '<=', 'contains', 'not_contains', 'in', 'not_in'];
        if (in_array($condition->getOperator(), $operatorsRequiringValue) && !$condition->getValue()) {
            $errors[] = 'Une valeur est requise pour cet opérateur';
        }
        
        return $errors;
    }

    /**
     * Génère un message d'erreur par défaut pour une condition
     */
    private function getDefaultErrorMessage(EventCondition $condition): string
    {
        $entityName = match($condition->getEntityClass()) {
            'App\\Entity\\User' => 'Utilisateur',
            'App\\Entity\\Event' => 'Événement',
            default => class_basename($condition->getEntityClass())
        };
        
        $attributes = $this->getAvailableAttributesForEntity($condition->getEntityClass());
        $attributeLabel = $attributes[$condition->getAttributeName()] ?? $condition->getAttributeName();
        
        $operators = $this->getAvailableOperators();
        $operatorLabel = $operators[$condition->getOperator()] ?? $condition->getOperator();
        
        return sprintf(
            'Condition non respectée : %s.%s %s %s',
            $entityName,
            $attributeLabel,
            $operatorLabel,
            $condition->getValue() ?? ''
        );
    }

    /**
     * Crée une condition simple pour un événement
     */
    public function createCondition(
        Event $event,
        string $entityClass,
        string $attributeName,
        string $operator,
        ?string $value = null,
        ?string $errorMessage = null
    ): EventCondition {
        $condition = new EventCondition();
        $condition->setEvent($event)
            ->setEntityClass($entityClass)
            ->setAttributeName($attributeName)
            ->setOperator($operator)
            ->setValue($value)
            ->setErrorMessage($errorMessage)
            ->setActive(true);
        
        return $condition;
    }
}