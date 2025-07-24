<?php

namespace App\Command;

use App\Service\UserTypeManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-user-types',
    description: 'Initialize default user types and their attributes'
)]
class InitUserTypesCommand extends Command
{
    public function __construct(
        private UserTypeManager $userTypeManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initializing User Types');

        try {
            $this->userTypeManager->initializeDefaultUserTypes();
            
            $io->success('Default user types have been initialized successfully!');
            
            $io->section('Available User Types:');
            $userTypes = $this->userTypeManager->getAllUserTypes();
            
            foreach ($userTypes as $userType) {
                $io->writeln(sprintf(
                    '• <info>%s</info> (%s) - %d attributes', 
                    $userType->getDisplayName(),
                    $userType->getName(),
                    $userType->getAttributes()->count()
                ));
                
                foreach ($userType->getAttributes() as $attribute) {
                    $required = $attribute->isRequired() ? ' <comment>(required)</comment>' : '';
                    $io->writeln(sprintf(
                        '  - %s (%s)%s',
                        $attribute->getDisplayName(),
                        $attribute->getAttributeType(),
                        $required
                    ));
                }
                $io->newLine();
            }

        } catch (\Exception $e) {
            $io->error('Failed to initialize user types: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}