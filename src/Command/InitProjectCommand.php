<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArrayInput;

#[AsCommand(
    name: 'app:init-project',
    description: 'Initialize a complete ZeTilt CMS project with all modules and demo data'
)]
class InitProjectCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🚀 Initialisation complète ZeTilt CMS');
        
        // Liste des commandes à exécuter dans l'ordre
        $commands = [
            ['cmd' => 'app:init-cms', 'desc' => 'Initialisation CMS de base'],
            ['cmd' => 'app:init-user-types', 'desc' => 'Types d\'utilisateurs'],
            ['cmd' => 'doctrine:fixtures:load', 'desc' => 'Données de base', 'args' => ['--no-interaction' => true]],
            ['cmd' => 'app:module:activate', 'desc' => 'Activation module UserPlus', 'args' => ['module' => 'UserPlus']],
            ['cmd' => 'app:module:activate', 'desc' => 'Activation module Events', 'args' => ['module' => 'Events']],
            ['cmd' => 'app:module:activate', 'desc' => 'Activation module Gallery', 'args' => ['module' => 'Gallery']],
            ['cmd' => 'app:module:activate', 'desc' => 'Activation module Articles', 'args' => ['module' => 'Articles']],
            ['cmd' => 'app:module:activate', 'desc' => 'Activation module Business', 'args' => ['module' => 'Business']],
            ['cmd' => 'app:create-demo-pages', 'desc' => 'Pages de démonstration'],
            ['cmd' => 'app:create-demo-galleries', 'desc' => 'Galeries de démonstration'],
            ['cmd' => 'app:configure-translation', 'desc' => 'Configuration des traductions'],
            ['cmd' => 'cache:clear', 'desc' => 'Nettoyage du cache'],
        ];
        
        $success = 0;
        $failed = 0;
        
        foreach ($commands as $commandData) {
            $commandName = $commandData['cmd'];
            $description = $commandData['desc'];
            $args = $commandData['args'] ?? [];
            
            $io->section("📋 {$description}");
            
            try {
                $command = $this->getApplication()->find($commandName);
                $arguments = array_merge(['command' => $commandName], $args);
                $greetInput = new ArrayInput($arguments);
                
                $result = $command->run($greetInput, $output);
                
                if ($result === 0) {
                    $io->success("✅ {$commandName}");
                    $success++;
                } else {
                    $io->warning("⚠️ {$commandName} - Code retour: {$result}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $io->error("❌ {$commandName} - Erreur: " . $e->getMessage());
                $failed++;
            }
        }
        
        // Commandes optionnelles (Prodigi)
        $io->section("🖼️ Initialisation cache photos Prodigi (optionnel)");
        try {
            $command = $this->getApplication()->find('prodigi:refresh-products');
            $arguments = ['command' => 'prodigi:refresh-products', '--force-all' => true];
            $greetInput = new ArrayInput($arguments);
            
            $result = $command->run($greetInput, $output);
            if ($result === 0) {
                $io->success("✅ Cache Prodigi initialisé");
                $success++;
            } else {
                $io->note("ℹ️ Cache Prodigi non initialisé (API non configurée)");
            }
        } catch (\Exception $e) {
            $io->note("ℹ️ Cache Prodigi ignoré: " . $e->getMessage());
        }
        
        // Résumé final
        $io->newLine();
        if ($failed > 0) {
            $io->warning("⚠️ Initialisation terminée avec {$failed} erreur(s) et {$success} succès");
            $io->text([
                "Certaines commandes ont échoué. Votre projet est probablement fonctionnel",
                "mais vous pourriez avoir besoin d'exécuter manuellement :",
                "• php bin/console doctrine:fixtures:load (pour l'utilisateur admin)",
                "• php bin/console app:module:activate UserPlus",
                "• php bin/console app:create-demo-pages"
            ]);
        } else {
            $io->success('🎉 Projet ZeTilt CMS initialisé avec succès !');
        }
        
        $io->definitionList(
            ['Accès admin' => 'http://localhost:8000/admin'],
            ['Email par défaut' => 'admin@zetilt.com'],
            ['Mot de passe par défaut' => 'Admin123!'],
            ['Commandes disponibles' => 'php bin/console list app:']
        );
        
        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}