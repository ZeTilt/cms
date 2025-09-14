<?php

namespace App\Command;

use App\Entity\Module;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-modules',
    description: 'Initialise les modules de base du système',
)]
class InitModulesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Modules de base à créer
        $modules = [
            [
                'name' => 'blog',
                'displayName' => 'Blog & Articles',
                'description' => 'Système de gestion d\'articles et de blog avec catégories et tags',
                'active' => true
            ],
            [
                'name' => 'events',
                'displayName' => 'Événements',
                'description' => 'Gestion des événements, calendrier et inscriptions',
                'active' => true
            ],
            [
                'name' => 'gallery',
                'displayName' => 'Galeries Photos',
                'description' => 'Gestion des galeries d\'images et photos',
                'active' => false
            ],
            [
                'name' => 'pages',
                'displayName' => 'Pages Statiques',
                'description' => 'Création et gestion de pages de contenu statique',
                'active' => true
            ],
            [
                'name' => 'contact',
                'displayName' => 'Formulaire de Contact',
                'description' => 'Formulaire de contact et gestion des messages',
                'active' => false
            ],
            [
                'name' => 'newsletter',
                'displayName' => 'Newsletter',
                'description' => 'Gestion des abonnés et envoi de newsletters',
                'active' => false
            ],
            [
                'name' => 'members',
                'displayName' => 'Gestion des Membres',
                'description' => 'Système de membres avec profils et adhésions',
                'active' => false
            ],
            [
                'name' => 'shop',
                'displayName' => 'Boutique',
                'description' => 'E-commerce simple pour vente d\'articles du club',
                'active' => false
            ]
        ];

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($modules as $moduleData) {
            // Vérifier si le module existe déjà
            $existingModule = $this->entityManager->getRepository(Module::class)
                ->findOneBy(['name' => $moduleData['name']]);

            if ($existingModule) {
                // Mettre à jour le module existant
                $existingModule->setDisplayName($moduleData['displayName']);
                $existingModule->setDescription($moduleData['description']);
                // Ne pas changer le statut actif s'il existe déjà
                
                $updatedCount++;
                $io->text("✅ Module '{$moduleData['displayName']}' mis à jour");
            } else {
                // Créer un nouveau module
                $module = new Module();
                $module->setName($moduleData['name']);
                $module->setDisplayName($moduleData['displayName']);
                $module->setDescription($moduleData['description']);
                $module->setActive($moduleData['active']);
                $module->setConfig([]); // Configuration vide par défaut

                $this->entityManager->persist($module);
                $createdCount++;
                
                $status = $moduleData['active'] ? '🟢' : '🔴';
                $io->text("➕ Module '{$moduleData['displayName']}' créé {$status}");
            }
        }

        $this->entityManager->flush();

        $io->success([
            'Initialisation des modules terminée !',
            "Modules créés: {$createdCount}",
            "Modules mis à jour: {$updatedCount}",
            '',
            'Modules activés par défaut:',
            '- Blog & Articles (gestion du contenu)',
            '- Événements (calendrier des activités)', 
            '- Pages Statiques (pages fixes)',
            '',
            'Rendez-vous dans Admin > Modules pour activer d\'autres fonctionnalités !'
        ]);

        return Command::SUCCESS;
    }
}