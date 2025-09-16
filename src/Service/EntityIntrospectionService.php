<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Event;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionMethod;

class EntityIntrospectionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {}

    /**
     * Découvre les attributs réels d'une entité via réflexion
     */
    public function discoverEntityAttributes(string $entityClass): array
    {
        if (!class_exists($entityClass)) {
            return [];
        }

        $reflection = new ReflectionClass($entityClass);
        $attributes = [];

        // Parcourir toutes les méthodes publiques
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            // Détecter les getters
            if (str_starts_with($methodName, 'get') && strlen($methodName) > 3) {
                $attributeName = lcfirst(substr($methodName, 3));
                $displayName = $this->generateDisplayName($attributeName);
                $attributes[$attributeName] = $displayName;
            }
            // Détecter les méthodes is/has
            elseif (str_starts_with($methodName, 'is') && strlen($methodName) > 2) {
                $attributeName = lcfirst(substr($methodName, 2));
                $displayName = $this->generateDisplayName($attributeName);
                $attributes[$attributeName] = $displayName;
            }
            elseif (str_starts_with($methodName, 'has') && strlen($methodName) > 3) {
                $attributeName = lcfirst(substr($methodName, 3));
                $displayName = $this->generateDisplayName($attributeName);
                $attributes[$attributeName] = $displayName;
            }
        }

        // Ajouter les attributs EAV pour User
        if ($entityClass === User::class) {
            $eavAttributes = $this->getEAVAttributes();
            $attributes = array_merge($attributes, $eavAttributes);
        }

        return $attributes;
    }

    /**
     * Récupère les valeurs possibles pour un attribut donné
     */
    public function getAttributeValues(string $entityClass, string $attributeName): array
    {
        // Valeurs spécifiques pour certains attributs connus
        $specificValues = $this->getSpecificAttributeValues($entityClass, $attributeName);
        if (!empty($specificValues)) {
            return $specificValues;
        }

        // Essayer de découvrir les valeurs depuis la base de données
        return $this->discoverValuesFromDatabase($entityClass, $attributeName);
    }

    /**
     * Génère un nom d'affichage convivial pour un attribut
     */
    private function generateDisplayName(string $attributeName): string
    {
        // Mappings spéciaux
        $mappings = [
            'firstName' => 'Prénom',
            'lastName' => 'Nom',
            'email' => 'Email',
            'status' => 'Statut',
            'active' => 'Actif',
            'emailVerified' => 'Email vérifié',
            'title' => 'Titre',
            'maxParticipants' => 'Nombre max participants',
            'currentParticipants' => 'Participants actuels',
            'location' => 'Lieu',
            'createdAt' => 'Date de création',
            'updatedAt' => 'Date de modification',
            'startDate' => 'Date de début',
            'endDate' => 'Date de fin',
        ];

        if (isset($mappings[$attributeName])) {
            return $mappings[$attributeName];
        }

        // Conversion automatique : camelCase vers mots séparés
        $words = preg_split('/(?=[A-Z])/', $attributeName);
        $words = array_filter($words); // Supprimer les éléments vides
        $words[0] = ucfirst($words[0] ?? '');
        
        return implode(' ', $words);
    }

    /**
     * Récupère les attributs EAV connus
     */
    private function getEAVAttributes(): array
    {
        return [
            'diving_level' => 'Niveau de plongée',
            'birth_date' => 'Date de naissance',
            'medical_certificate_date' => 'Date certificat médical',
            'swimming_test_date' => 'Date test natation',
            'freediver' => 'Apnéiste',
            'emergency_contact_name' => 'Contact urgence (nom)',
            'emergency_contact_phone' => 'Contact urgence (téléphone)',
            'insurance_number' => 'N° d\'assurance',
            'license_number' => 'N° de licence',
            'club_member_since' => 'Membre du club depuis',
        ];
    }

    /**
     * Valeurs spécifiques pour certains attributs
     */
    private function getSpecificAttributeValues(string $entityClass, string $attributeName): array
    {
        if ($entityClass === User::class) {
            switch ($attributeName) {
                case 'status':
                    return [
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        'suspended' => 'Suspendu'
                    ];
                
                case 'diving_level':
                    return [
                        'N1' => 'N1 - Plongeur Encadré 20m',
                        'N2' => 'N2 - Plongeur Autonome 20m',
                        'N3' => 'N3 - Plongeur Autonome 60m',
                        'N4' => 'N4 - Guide de Palanquée',
                        'N5' => 'N5 - Directeur de Plongée',
                        'E1' => 'E1 - Initiateur',
                        'E2' => 'E2 - Moniteur Fédéral 1er',
                        'E3' => 'E3 - Moniteur Fédéral 2ème',
                        'E4' => 'E4 - Moniteur Fédéral 3ème',
                        'RIFAP' => 'RIFAP'
                    ];
                
                case 'active':
                case 'emailVerified':
                case 'freediver':
                    return [
                        '1' => 'Oui',
                        '0' => 'Non'
                    ];
            }
        }

        if ($entityClass === Event::class) {
            switch ($attributeName) {
                case 'status':
                    return [
                        'active' => 'Actif',
                        'cancelled' => 'Annulé',
                        'completed' => 'Terminé',
                        'draft' => 'Brouillon'
                    ];
                
                case 'type':
                    return [
                        'training' => 'Formation',
                        'dive' => 'Plongée',
                        'trip' => 'Sortie',
                        'meeting' => 'Réunion',
                        'maintenance' => 'Maintenance',
                        'event' => 'Événement'
                    ];
            }
        }

        return [];
    }

    /**
     * Essaie de découvrir les valeurs depuis la base de données
     */
    private function discoverValuesFromDatabase(string $entityClass, string $attributeName): array
    {
        try {
            $repository = $this->entityManager->getRepository($entityClass);
            $queryBuilder = $repository->createQueryBuilder('e');
            
            // Construire le nom de méthode getter
            $getterMethod = 'get' . ucfirst($attributeName);
            
            // Vérifier si la méthode existe
            if (method_exists($entityClass, $getterMethod)) {
                $queryBuilder->select("DISTINCT e.{$attributeName}")
                    ->where("e.{$attributeName} IS NOT NULL")
                    ->orderBy("e.{$attributeName}", 'ASC');
                
                $results = $queryBuilder->getQuery()->getScalarResult();
                
                $values = [];
                foreach ($results as $result) {
                    $value = $result[$attributeName];
                    if (!empty($value)) {
                        $values[$value] = $value;
                    }
                }
                
                return $values;
            }
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide
        }

        return [];
    }

    /**
     * Suggère des opérateurs appropriés pour un attribut
     */
    public function suggestOperators(string $entityClass, string $attributeName): array
    {
        $allOperators = [
            '=' => 'Égal à',
            '!=' => 'Différent de',
            '>' => 'Supérieur à',
            '>=' => 'Supérieur ou égal à',
            '<' => 'Inférieur à',
            '<=' => 'Inférieur ou égal à',
            'contains' => 'Contient',
            'not_contains' => 'Ne contient pas',
            'in' => 'Dans la liste',
            'not_in' => 'Pas dans la liste',
            'exists' => 'Existe (non vide)',
            'not_exists' => 'N\'existe pas (vide)'
        ];

        // Opérateurs suggérés selon le type d'attribut
        $numericAttributes = ['age', 'maxParticipants', 'currentParticipants'];
        $dateAttributes = ['Date', 'createdAt', 'updatedAt', 'startDate', 'endDate', 'birth_date', 'medical_certificate_date'];
        $booleanAttributes = ['active', 'emailVerified', 'freediver'];
        $enumAttributes = ['status', 'diving_level', 'type'];

        if (in_array($attributeName, $numericAttributes) || str_contains($attributeName, 'age')) {
            return array_slice($allOperators, 0, 6, true); // =, !=, >, >=, <, <=
        }

        if (in_array($attributeName, $booleanAttributes) || str_ends_with($attributeName, 'Verified')) {
            return ['=' => 'Égal à', '!=' => 'Différent de'];
        }

        if (in_array($attributeName, $enumAttributes)) {
            return [
                '=' => 'Égal à',
                '!=' => 'Différent de',
                'in' => 'Dans la liste',
                'not_in' => 'Pas dans la liste'
            ];
        }

        foreach ($dateAttributes as $dateAttr) {
            if (str_contains($attributeName, $dateAttr)) {
                return array_slice($allOperators, 0, 6, true); // =, !=, >, >=, <, <=
            }
        }

        return $allOperators; // Tous les opérateurs par défaut
    }
}