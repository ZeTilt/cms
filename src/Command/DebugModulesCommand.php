<?php

namespace App\Command;

use App\Service\ModuleManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-modules',
    description: 'Debug module status',
)]
class DebugModulesCommand extends Command
{
    public function __construct(
        private ModuleManager $moduleManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Debug des Modules');

        // Afficher tous les modules
        $modules = $this->moduleManager->getAllModules();
        
        $rows = [];
        foreach ($modules as $module) {
            $rows[] = [
                $module->getName(),
                $module->getDisplayName(),
                $module->isActive() ? '✅ Oui' : '❌ Non',
                implode(', ', $this->moduleManager->getModuleDependencies($module->getName()))
            ];
        }

        $io->table(['Nom', 'Affichage', 'Actif', 'Dépendances'], $rows);

        // Test spécifique du module shop
        $io->section('Test du module Shop');
        $shopActive = $this->moduleManager->isModuleActive('shop');
        $io->writeln("Module Shop actif: " . ($shopActive ? '✅ Oui' : '❌ Non'));

        $galleryActive = $this->moduleManager->isModuleActive('gallery');
        $io->writeln("Module Gallery actif: " . ($galleryActive ? '✅ Oui' : '❌ Non'));

        return Command::SUCCESS;
    }
}