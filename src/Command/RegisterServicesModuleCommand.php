<?php

namespace App\Command;

use App\Service\ModuleManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:module:register-services',
    description: 'Register the Services module',
)]
class RegisterServicesModuleCommand extends Command
{
    public function __construct(
        private ModuleManager $moduleManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Registering Services Module');

        // Register the Services module
        $module = $this->moduleManager->registerModule(
            'services',
            'Services Module',
            'Manage business services and packages with pricing, features, and booking options',
            [
                'default_currency' => 'EUR',
                'enable_booking' => true,
                'enable_categories' => true,
                'enable_features' => true,
                'pricing_types' => ['fixed', 'per_hour', 'per_day', 'per_session', 'custom'],
            ]
        );

        $io->success(sprintf('Services module registered successfully with ID: %d', $module->getId()));

        // Activate the module
        $this->moduleManager->activateModule('services');
        $io->success('Services module activated');

        $io->note([
            'The Services module has been registered and activated with default settings.',
            'You can manage services at /admin/services',
            'Services can be displayed on the public website and integrated with bookings.',
        ]);

        return Command::SUCCESS;
    }
}