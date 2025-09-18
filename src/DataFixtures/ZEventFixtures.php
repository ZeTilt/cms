<?php

namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Entity\EventType;
use Doctrine\Persistence\ObjectManager;

class ZEventFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer les EventTypes depuis la base de données
        $eventTypeRepo = $manager->getRepository(EventType::class);
        $eventTypes = [
            'formation' => $eventTypeRepo->findOneBy(['code' => 'formation']),
            'sortie' => $eventTypeRepo->findOneBy(['code' => 'sortie']),
            'apnee' => $eventTypeRepo->findOneBy(['code' => 'apnee']),
            'bapteme' => $eventTypeRepo->findOneBy(['code' => 'bapteme']),
            'reunion' => $eventTypeRepo->findOneBy(['code' => 'reunion']),
            'evenement' => $eventTypeRepo->findOneBy(['code' => 'evenement']),
            'sejour' => $eventTypeRepo->findOneBy(['code' => 'sejour']),
            'entrainement' => $eventTypeRepo->findOneBy(['code' => 'entrainement']),
            'permanence' => $eventTypeRepo->findOneBy(['code' => 'permanence']),
            'examen' => $eventTypeRepo->findOneBy(['code' => 'examen']),
        ];
        $events = [
            // Septembre 2025
            [
                'title' => 'Séjour PNVTT Metz',
                'description' => 'Séjour de plongée à Metz avec le groupe PNVTT. Plusieurs jours de plongée et découverte des sites locaux.',
                'startDate' => '2025-09-01 08:00:00',
                'endDate' => '2025-09-03 18:00:00',
                'location' => 'Metz',
                'eventTypeCode' => 'sejour',
                'maxParticipants' => 20,
                'status' => 'active'
            ],
            [
                'title' => 'Formation MF1',
                'description' => 'Formation moniteur fédéral 1er degré. Session intensive sur deux jours.',
                'startDate' => '2025-09-01 09:00:00',
                'endDate' => '2025-09-02 17:00:00',
                'location' => 'Club CSV',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 8,
                'status' => 'active'
            ],
            [
                'title' => 'Plongée Tombant Brannec',
                'description' => 'Exploration du tombant de Brannec. Plongée réservée aux niveaux 2 et plus. Départ du port de Vannes.',
                'startDate' => '2025-09-04 08:30:00',
                'endDate' => '2025-09-04 13:00:00',
                'location' => 'Tombant Brannec - Golfe du Morbihan',
                'eventTypeCode' => 'sortie',
                'maxParticipants' => 16,
                'status' => 'active'
            ],
            [
                'title' => 'Apnée Carrière St AVÉ',
                'description' => 'Séance d\'apnée à la carrière de St AVÉ. Travail de la profondeur et techniques de compensation.',
                'startDate' => '2025-09-04 14:00:00',
                'endDate' => '2025-09-04 17:00:00',
                'location' => 'Carrière St AVÉ',
                'eventTypeCode' => 'apnee',
                'maxParticipants' => 12,
                'status' => 'active'
            ],
            [
                'title' => 'Comité Directeur',
                'description' => 'Réunion mensuelle du comité directeur du club.',
                'startDate' => '2025-09-05 19:00:00',
                'endDate' => '2025-09-05 22:00:00',
                'location' => 'Club CSV',
                'eventTypeCode' => 'reunion',
                'maxParticipants' => null,
                'status' => 'active'
            ],
            [
                'title' => 'Forum des Associations',
                'description' => 'Présence du club au forum des associations de Vannes. Venez nous rencontrer et découvrir nos activités !',
                'startDate' => '2025-09-06 09:00:00',
                'endDate' => '2025-09-06 18:00:00',
                'location' => 'Palais des Arts - Vannes',
                'eventTypeCode' => 'evenement',
                'maxParticipants' => null,
                'status' => 'active'
            ],
            [
                'title' => 'Plongée Golfe avec FDC',
                'description' => 'Sortie plongée dans le Golfe du Morbihan avec le Fleur de Corail. Deux plongées prévues.',
                'startDate' => '2025-09-06 08:00:00',
                'endDate' => '2025-09-06 14:00:00',
                'location' => 'Golfe du Morbihan',
                'eventTypeCode' => 'sortie',
                'maxParticipants' => 20,
                'status' => 'active'
            ],
            [
                'title' => 'Formation MF1 - Session 2',
                'description' => 'Deuxième session de formation MF1 du mois.',
                'startDate' => '2025-09-12 09:00:00',
                'endDate' => '2025-09-13 17:00:00',
                'location' => 'Club CSV',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 8,
                'status' => 'active'
            ],
            [
                'title' => 'Qualification Nitrox',
                'description' => 'Formation qualification Nitrox. Théorie et pratique. Encadrant : Francis RICHET',
                'startDate' => '2025-09-15 09:00:00',
                'endDate' => '2025-09-15 17:00:00',
                'location' => 'Questembert',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 15,
                'status' => 'active'
            ],
            [
                'title' => 'Comité Directeur',
                'description' => 'Réunion du comité directeur - préparation rentrée.',
                'startDate' => '2025-09-18 19:00:00',
                'endDate' => '2025-09-18 22:00:00',
                'location' => 'Club CSV',
                'eventTypeCode' => 'reunion',
                'maxParticipants' => null,
                'status' => 'active'
            ],
            [
                'title' => 'Qualification Nitrox',
                'description' => 'Formation qualification Nitrox. Encadrant : Francis RICHET',
                'startDate' => '2025-09-20 09:00:00',
                'endDate' => '2025-09-20 17:00:00',
                'location' => 'Club CSV',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 20,
                'status' => 'active'
            ],
            [
                'title' => 'Formation N2 + Plongée bord Lorient',
                'description' => 'Formation niveau 2 avec plongée du bord à Lorient. Pratique des exercices techniques.',
                'startDate' => '2025-09-21 08:00:00',
                'endDate' => '2025-09-21 17:00:00',
                'location' => 'Lorient',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 12,
                'status' => 'active'
            ],
            [
                'title' => 'Qualification Nitrox',
                'description' => 'Formation qualification Nitrox. Encadrant : Francis RICHET',
                'startDate' => '2025-09-24 09:00:00',
                'endDate' => '2025-09-24 17:00:00',
                'location' => 'Club CSV',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 20,
                'status' => 'active'
            ],
            [
                'title' => 'Réception groupe Honfleur',
                'description' => 'Accueil et plongée avec le groupe de plongeurs d\'Honfleur. Découverte des sites du Golfe.',
                'startDate' => '2025-09-27 09:00:00',
                'endDate' => '2025-09-27 17:00:00',
                'location' => 'Golfe du Morbihan',
                'eventTypeCode' => 'evenement',
                'maxParticipants' => 30,
                'status' => 'active'
            ],
            [
                'title' => 'Qualification Nitrox',
                'description' => 'Dernière session de qualification Nitrox du mois. Encadrant : Francis RICHET',
                'startDate' => '2025-09-27 09:00:00',
                'endDate' => '2025-09-27 17:00:00',
                'location' => 'Club CSV',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 15,
                'status' => 'active'
            ],
            [
                'title' => 'Formation N2 Golfe',
                'description' => 'Formation niveau 2 dans le Golfe du Morbihan. Exercices techniques et exploration.',
                'startDate' => '2025-09-28 08:00:00',
                'endDate' => '2025-09-28 14:00:00',
                'location' => 'Golfe du Morbihan',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 10,
                'status' => 'active'
            ],

            // Octobre 2025
            [
                'title' => 'Comité Directeur Codep 56',
                'description' => 'Réunion du comité directeur départemental FFESSM Morbihan.',
                'startDate' => '2025-10-01 19:00:00',
                'endDate' => '2025-10-01 22:00:00',
                'location' => 'Codep 56 - Vannes',
                'eventTypeCode' => 'reunion',
                'maxParticipants' => null,
                'status' => 'active'
            ],
            [
                'title' => 'Formation N1 UBS - Théorie',
                'description' => 'Cours théorique pour la formation niveau 1, en partenariat avec l\'UBS.',
                'startDate' => '2025-10-02 19:00:00',
                'endDate' => '2025-10-02 21:00:00',
                'location' => 'UBS Vannes',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 20,
                'status' => 'active'
            ],
            [
                'title' => 'Examen MEF1',
                'description' => 'Examen moniteur entraîneur fédéral 1er degré.',
                'startDate' => '2025-10-05 08:00:00',
                'endDate' => '2025-10-05 18:00:00',
                'location' => 'Club CSV',
                'eventTypeCode' => 'examen',
                'maxParticipants' => 12,
                'status' => 'active'
            ],
            [
                'title' => 'Technique N3 - Perfectionnement',
                'description' => 'Session technique pour les niveaux 3. Perfectionnement des techniques de plongée profonde.',
                'startDate' => '2025-10-11 14:00:00',
                'endDate' => '2025-10-11 17:00:00',
                'location' => 'Piscine + Fosse',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 8,
                'status' => 'active'
            ],
            [
                'title' => 'Sortie Houat - Exploration',
                'description' => 'Sortie exploration à Houat. Sites profonds, réservée aux N2 confirmés et N3.',
                'startDate' => '2025-10-12 07:00:00',
                'endDate' => '2025-10-12 18:00:00',
                'location' => 'Île de Houat',
                'eventTypeCode' => 'sortie',
                'maxParticipants' => 16,
                'status' => 'active'
            ],
            [
                'title' => 'Technique N3 - Session 2',
                'description' => 'Deuxième session technique niveau 3 du mois.',
                'startDate' => '2025-10-18 14:00:00',
                'endDate' => '2025-10-18 17:00:00',
                'location' => 'Piscine + Fosse',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 8,
                'status' => 'active'
            ],
            [
                'title' => 'Baptêmes découverte',
                'description' => 'Journée baptêmes de plongée pour découvrir l\'activité. Ouvert à tous à partir de 8 ans.',
                'startDate' => '2025-10-19 09:00:00',
                'endDate' => '2025-10-19 17:00:00',
                'location' => 'Piscine municipale',
                'eventTypeCode' => 'bapteme',
                'maxParticipants' => 30,
                'status' => 'active'
            ],
            [
                'title' => 'Formation N1 Golfe - Pratique',
                'description' => 'Session pratique de formation niveau 1 dans le Golfe du Morbihan.',
                'startDate' => '2025-10-25 08:00:00',
                'endDate' => '2025-10-25 14:00:00',
                'location' => 'Golfe du Morbihan',
                'eventTypeCode' => 'formation',
                'maxParticipants' => 12,
                'status' => 'active'
            ],
            [
                'title' => 'Assemblée Générale CSV',
                'description' => 'Assemblée générale annuelle du Club Subaquatique des Vénètes. Bilan de l\'année et perspectives.',
                'startDate' => '2025-10-31 19:00:00',
                'endDate' => '2025-10-31 23:00:00',
                'location' => 'Salle des fêtes - Vannes',
                'eventTypeCode' => 'evenement',
                'maxParticipants' => null,
                'status' => 'active'
            ],

            // Événements récurrents hebdomadaires
            [
                'title' => 'Entraînement Piscine',
                'description' => 'Entraînement hebdomadaire en piscine. Technique, apnée et nage avec palmes.',
                'startDate' => '2025-09-02 19:00:00',
                'endDate' => '2025-09-02 21:00:00',
                'location' => 'Piscine Kercado - Vannes',
                'eventTypeCode' => 'entrainement',
                'maxParticipants' => 30,
                'status' => 'active',
                'isRecurring' => true,
                'recurrenceType' => 'weekly',
                'recurrenceInterval' => 1,
                'recurrenceWeekdays' => ['tuesday', 'thursday'],
                'recurrenceEndDate' => '2025-12-31'
            ],
            [
                'title' => 'Permanence Club',
                'description' => 'Permanence au local du club pour inscriptions et renseignements.',
                'startDate' => '2025-09-05 18:00:00',
                'endDate' => '2025-09-05 20:00:00',
                'location' => 'Local CSV - 5 Av. du Président Wilson',
                'eventTypeCode' => 'permanence',
                'maxParticipants' => null,
                'status' => 'active',
                'isRecurring' => true,
                'recurrenceType' => 'weekly',
                'recurrenceInterval' => 1,
                'recurrenceWeekdays' => ['friday'],
                'recurrenceEndDate' => '2025-12-31'
            ]
        ];

        foreach ($events as $eventData) {
            $event = new Event();
            $event->setTitle($eventData['title']);
            $event->setDescription($eventData['description']);
            $event->setStartDate(new \DateTime($eventData['startDate']));
            
            if (isset($eventData['endDate'])) {
                $event->setEndDate(new \DateTime($eventData['endDate']));
            }
            
            $event->setLocation($eventData['location']);
            $eventType = $eventTypes[$eventData['eventTypeCode']] ?? null;
            if (!$eventType) {
                throw new \Exception('EventType not found: ' . $eventData['eventTypeCode']);
            }
            $event->setEventType($eventType);
            $event->setMaxParticipants($eventData['maxParticipants']);
            $event->setStatus($eventData['status']);
            
            // Gestion de la récurrence
            if (isset($eventData['isRecurring']) && $eventData['isRecurring']) {
                $event->setRecurring(true);
                $event->setRecurrenceType($eventData['recurrenceType']);
                $event->setRecurrenceInterval($eventData['recurrenceInterval']);
                
                if (isset($eventData['recurrenceWeekdays'])) {
                    $event->setRecurrenceWeekdays($eventData['recurrenceWeekdays']);
                }
                
                if (isset($eventData['recurrenceEndDate'])) {
                    $event->setRecurrenceEndDate(new \DateTime($eventData['recurrenceEndDate']));
                }
            }

            $manager->persist($event);
        }

        $manager->flush();
    }
}