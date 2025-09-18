<?php

namespace App\DataFixtures;

use App\Entity\EventType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventTypeFixtures extends Fixture
{
    public const FORMATION_REFERENCE = 'event-type-formation';
    public const SORTIE_REFERENCE = 'event-type-sortie';
    public const APNEE_REFERENCE = 'event-type-apnee';
    public const BAPTEME_REFERENCE = 'event-type-bapteme';
    public const REUNION_REFERENCE = 'event-type-reunion';
    public const EVENEMENT_REFERENCE = 'event-type-evenement';
    public const SEJOUR_REFERENCE = 'event-type-sejour';
    public const ENTRAINEMENT_REFERENCE = 'event-type-entrainement';
    public const PERMANENCE_REFERENCE = 'event-type-permanence';
    public const EXAMEN_REFERENCE = 'event-type-examen';

    public function load(ObjectManager $manager): void
    {
        $eventTypes = [
            [
                'name' => 'Formation',
                'code' => 'formation',
                'color' => '#FF0000',
                'description' => 'Formation et cours théoriques/pratiques',
                'reference' => self::FORMATION_REFERENCE
            ],
            [
                'name' => 'Sortie Plongée',
                'code' => 'sortie',
                'color' => '#0080FF',
                'description' => 'Sorties et explorations en plongée',
                'reference' => self::SORTIE_REFERENCE
            ],
            [
                'name' => 'Apnée',
                'code' => 'apnee',
                'color' => '#00CED1',
                'description' => 'Activités d\'apnée et plongée libre',
                'reference' => self::APNEE_REFERENCE
            ],
            [
                'name' => 'Baptême',
                'code' => 'bapteme',
                'color' => '#00FF00',
                'description' => 'Baptêmes et découverte de la plongée',
                'reference' => self::BAPTEME_REFERENCE
            ],
            [
                'name' => 'Réunion',
                'code' => 'reunion',
                'color' => '#808080',
                'description' => 'Réunions et assemblées',
                'reference' => self::REUNION_REFERENCE
            ],
            [
                'name' => 'Événement',
                'code' => 'evenement',
                'color' => '#32CD32',
                'description' => 'Événements spéciaux et manifestations',
                'reference' => self::EVENEMENT_REFERENCE
            ],
            [
                'name' => 'Séjour',
                'code' => 'sejour',
                'color' => '#FFA500',
                'description' => 'Séjours et voyages plongée',
                'reference' => self::SEJOUR_REFERENCE
            ],
            [
                'name' => 'Entraînement',
                'code' => 'entrainement',
                'color' => '#00BFFF',
                'description' => 'Entraînements piscine et technique',
                'reference' => self::ENTRAINEMENT_REFERENCE
            ],
            [
                'name' => 'Permanence',
                'code' => 'permanence',
                'color' => '#D3D3D3',
                'description' => 'Permanences du club',
                'reference' => self::PERMANENCE_REFERENCE
            ],
            [
                'name' => 'Examen',
                'code' => 'examen',
                'color' => '#DC143C',
                'description' => 'Examens et évaluations',
                'reference' => self::EXAMEN_REFERENCE
            ]
        ];

        foreach ($eventTypes as $typeData) {
            $eventType = new EventType();
            $eventType->setName($typeData['name']);
            $eventType->setCode($typeData['code']);
            $eventType->setColor($typeData['color']);
            $eventType->setDescription($typeData['description']);
            $eventType->setActive(true);

            $manager->persist($eventType);
            
            // Ajouter une référence pour pouvoir l'utiliser dans d'autres fixtures
            $this->addReference($typeData['reference'], $eventType);
        }

        $manager->flush();
    }
}