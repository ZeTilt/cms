<?php

namespace App\Command;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-plongee-events',
    description: 'Créer des événements de démonstration pour le club de plongée'
)]
class CreatePlongeeEventsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vider les événements existants
        $this->entityManager->getConnection()->executeStatement('DELETE FROM event');

        $events = [
            [
                'title' => 'Formation Niveau 1 - Théorie',
                'description' => "Formation théorique pour l'obtention du niveau 1 FFESSM.\n\nAu programme :\n• Physique et physiologie de la plongée\n• Matériel et équipement\n• Sécurité en plongée\n• Réglementation\n\nMatériel requis : Manuel N1, cahier de notes",
                'startDate' => new \DateTime('next saturday 10:00'),
                'endDate' => new \DateTime('next saturday 12:00'),
                'location' => 'Salle de formation - Piscine Océanis',
                'type' => 'training',
                'maxParticipants' => 15,
                'currentParticipants' => 8,
                'color' => '#3B82F6'
            ],
            [
                'title' => 'Plongée technique Conleau',
                'description' => "Plongée technique sur le site de Conleau pour plongeurs niveau 2 minimum.\n\nObjectifs :\n• Perfectionnement des techniques de palmage\n• Exercices de sauvetage\n• Navigation sous-marine\n\nProfondeur max : 20m\nVisibilité attendue : 8-12m",
                'startDate' => new \DateTime('next sunday 09:30'),
                'endDate' => new \DateTime('next sunday 12:00'),
                'location' => 'Port de Conleau, Vannes',
                'type' => 'dive',
                'maxParticipants' => 12,
                'currentParticipants' => 9,
                'color' => '#FD7E29'
            ],
            [
                'title' => 'Sortie Belle-Île weekend',
                'description' => "Weekend plongée à Belle-Île-en-Mer avec hébergement.\n\nProgramme :\n• Samedi : 2 plongées (Port Coton, Les Grands Sables)\n• Dimanche : 2 plongées (Pointe des Poulains, Port Donnant)\n\nNiveau requis : N2 minimum\nHébergement : Camping Les Glacis\nTransport : Navette + ferry inclus",
                'startDate' => new \DateTime('+2 weeks saturday 07:00'),
                'endDate' => new \DateTime('+2 weeks sunday 19:00'),
                'location' => 'Belle-Île-en-Mer',
                'type' => 'trip',
                'maxParticipants' => 16,
                'currentParticipants' => 12,
                'color' => '#10B981'
            ],
            [
                'title' => 'Formation Nitrox élémentaire',
                'description' => "Formation à la plongée au nitrox (mélange air enrichi).\n\nContenu :\n• Théorie des mélanges gazeux\n• Avantages et contraintes du nitrox\n• Utilisation des tables et ordinateurs nitrox\n• Analyse des mélanges\n• Plongée pratique\n\nPré-requis : Niveau 2 FFESSM ou équivalent",
                'startDate' => new \DateTime('+1 week saturday 14:00'),
                'endDate' => new \DateTime('+1 week saturday 18:00'),
                'location' => 'Centre de formation + Bassin',
                'type' => 'training',
                'maxParticipants' => 8,
                'currentParticipants' => 6,
                'color' => '#3B82F6'
            ],
            [
                'title' => 'Assemblée Générale Annuelle',
                'description' => "Assemblée générale ordinaire du club.\n\nOrdre du jour :\n• Rapport moral du président\n• Rapport financier du trésorier\n• Bilan des activités 2024\n• Programme 2025\n• Élections du bureau\n• Questions diverses\n\nTous les membres sont invités à participer.",
                'startDate' => new \DateTime('+3 weeks saturday 20:00'),
                'endDate' => new \DateTime('+3 weeks saturday 22:30'),
                'location' => 'Salle des fêtes de Séné',
                'type' => 'meeting',
                'maxParticipants' => null,
                'currentParticipants' => 0,
                'color' => '#8B5CF6'
            ],
            [
                'title' => 'Maintenance matériel club',
                'description' => "Matinée dédiée à l'entretien et la vérification du matériel du club.\n\nTâches prévues :\n• Révision des détendeurs\n• Contrôle des gilets stabilisateurs\n• Test des combinaisons\n• Inventaire du matériel pédagogique\n• Nettoyage du local\n\nApportez vos outils si possible !",
                'startDate' => new \DateTime('+1 week saturday 08:00'),
                'endDate' => new \DateTime('+1 week saturday 12:00'),
                'location' => 'Local du club - Port de Vannes',
                'type' => 'maintenance',
                'maxParticipants' => 10,
                'currentParticipants' => 5,
                'color' => '#EAB308'
            ],
            [
                'title' => 'Plongée de nuit - Golfe du Morbihan',
                'description' => "Plongée nocturne exceptionnelle dans le Golfe du Morbihan.\n\nDécouvrez la faune nocturne :\n• Observation des seiches et poulpes\n• Fluorescence des organismes marins\n• Atmosphère unique de la plongée de nuit\n\nNiveau requis : N2 confirmé\nÉquipement : Phare de plongée obligatoire",
                'startDate' => new \DateTime('+10 days friday 20:30'),
                'endDate' => new \DateTime('+10 days friday 23:00'),
                'location' => 'Île aux Moines',
                'type' => 'dive',
                'maxParticipants' => 8,
                'currentParticipants' => 7,
                'color' => '#FD7E29'
            ],
            [
                'title' => 'Journée Découverte Plongée',
                'description' => "Journée portes ouvertes pour faire découvrir la plongée au grand public.\n\nActivités proposées :\n• Baptêmes de plongée en piscine\n• Présentation du matériel\n• Démonstrations de sécurité\n• Stands d'information\n• Exposition photos sous-marines\n\nOuvert à tous, débutants bienvenus !",
                'startDate' => new \DateTime('+4 weeks saturday 09:00'),
                'endDate' => new \DateTime('+4 weeks saturday 17:00'),
                'location' => 'Piscine Océanis Vannes',
                'type' => 'event',
                'maxParticipants' => 50,
                'currentParticipants' => 23,
                'color' => '#EF4444'
            ],
            [
                'title' => 'Formation Secourisme - PSC1',
                'description' => "Formation aux premiers secours civiques de niveau 1.\n\nCompétences acquises :\n• Protection et alerte\n• Victime qui s'étouffe\n• Victime qui saigne abondamment\n• Victime inconsciente qui respire\n• Victime en arrêt cardiaque\n• Malaise et traumatisme\n\nFormation certifiante obligatoire pour les encadrants.",
                'startDate' => new \DateTime('+2 weeks sunday 09:00'),
                'endDate' => new \DateTime('+2 weeks sunday 17:00'),
                'location' => 'Centre de formation - Séné',
                'type' => 'training',
                'maxParticipants' => 12,
                'currentParticipants' => 8,
                'color' => '#3B82F6'
            ],
            [
                'title' => 'Plongée épave - Barge du Rohu',
                'description' => "Exploration de l\'épave de la barge du Rohu, site emblématique du Golfe.\n\nCaractéristiques :\n• Profondeur : 15-18 mètres\n• Épave de 40 mètres de long\n• Riche faune fixée\n• Niveau requis : N1 accompagné ou N2 autonome\n\nPoint fort : Bancs de sars et dorades !",
                'startDate' => new \DateTime('next thursday 18:30'),
                'endDate' => new \DateTime('next thursday 20:30'),
                'location' => 'Port du Rohu - Locoal-Mendon',
                'type' => 'dive',
                'maxParticipants' => 10,
                'currentParticipants' => 6,
                'color' => '#FD7E29'
            ],
        ];

        foreach ($events as $eventData) {
            $event = new Event();
            $event->setTitle($eventData['title']);
            $event->setDescription($eventData['description']);
            $event->setStartDate($eventData['startDate']);
            $event->setEndDate($eventData['endDate']);
            $event->setLocation($eventData['location']);
            $event->setType($eventData['type']);
            $event->setMaxParticipants($eventData['maxParticipants']);
            $event->setCurrentParticipants($eventData['currentParticipants']);
            $event->setColor($eventData['color']);

            $this->entityManager->persist($event);
        }

        $this->entityManager->flush();

        $io->success(sprintf('✅ %d événements de plongée créés avec succès !', count($events)));
        $io->note('Le calendrier contient maintenant des formations, plongées, sorties et événements variés.');

        return Command::SUCCESS;
    }
}