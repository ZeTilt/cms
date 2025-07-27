<?php

namespace App\Command;

use App\Service\ModuleManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:module:register-registration',
    description: 'Register the Registration module',
)]
class RegisterRegistrationModuleCommand extends Command
{
    public function __construct(
        private ModuleManager $moduleManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Registering Registration Module');

        // Register the Registration module
        $module = $this->moduleManager->registerModule(
            'registration',
            'Registration Module',
            'Handles user registration with approval workflow and email verification',
            [
                'enabled' => true,
                'require_approval' => false,
                'email_verification' => false,
                'default_role' => 'ROLE_USER',
                'allowed_user_types' => [],
                'default_user_type' => null,
                'welcome_message' => 'Welcome! Please fill out the form below to create your account.',
                'terms_required' => false,
                'terms_url' => '',
            ]
        );

        $io->success(sprintf('Registration module registered successfully with ID: %d', $module->getId()));

        // Activate the module
        $this->moduleManager->activateModule('registration');
        $io->success('Registration module activated');

        $io->note([
            'The Registration module has been registered and activated with default settings.',
            'You can configure the module settings at /admin/registration/settings',
            'Access the registration dashboard at /admin/registration',
            'Users can register at /registration'
        ]);

        return Command::SUCCESS;
    }
}