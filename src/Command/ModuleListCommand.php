<?php

namespace App\Command;

use App\Service\ModuleManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:module:list',
    description: 'List all modules and their status',
)]
class ModuleListCommand extends Command
{
    public function __construct(
        private ModuleManager $moduleManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $modules = $this->moduleManager->getAllModules();

        if (empty($modules)) {
            $io->info('No modules found. Register some modules first.');
            return Command::SUCCESS;
        }

        $io->title('ZeTilt CMS Modules');

        $tableData = [];
        foreach ($modules as $module) {
            $tableData[] = [
                $module->getName(),
                $module->getDisplayName(),
                $module->isActive() ? '<fg=green>✓ Active</>' : '<fg=red>✗ Inactive</>',
                $module->getDescription() ?: 'No description',
            ];
        }

        $io->table(['Name', 'Display Name', 'Status', 'Description'], $tableData);

        return Command::SUCCESS;
    }
}