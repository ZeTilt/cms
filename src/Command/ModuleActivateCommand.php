<?php

namespace App\Command;

use App\Service\ModuleManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:module:activate',
    description: 'Activate a module',
)]
class ModuleActivateCommand extends Command
{
    public function __construct(
        private ModuleManager $moduleManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('module', InputArgument::REQUIRED, 'Module name to activate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $moduleName = $input->getArgument('module');

        if ($this->moduleManager->activateModule($moduleName)) {
            $io->success(sprintf('Module "%s" has been activated.', $moduleName));
            return Command::SUCCESS;
        }

        $io->error(sprintf('Module "%s" not found.', $moduleName));
        return Command::FAILURE;
    }
}