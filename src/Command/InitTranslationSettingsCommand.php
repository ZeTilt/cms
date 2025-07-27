<?php

namespace App\Command;

use App\Service\SettingsManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-translation-settings',
    description: 'Initialise les paramètres de traduction par défaut',
)]
class InitTranslationSettingsCommand extends Command
{
    public function __construct(
        private SettingsManager $settingsManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des paramètres de traduction');

        // Initialiser les paramètres de traduction par défaut s'ils n'existent pas
        if (!$this->settingsManager->has('translation.default_locale')) {
            $this->settingsManager->set('translation.default_locale', 'fr', 'Langue par défaut du site');
            $io->success('Langue par défaut définie à "fr"');
        } else {
            $io->note('Langue par défaut déjà configurée: ' . $this->settingsManager->get('translation.default_locale'));
        }

        if (!$this->settingsManager->has('translation.supported_locales')) {
            $this->settingsManager->set('translation.supported_locales', ['fr', 'en'], 'Langues supportées par le système de traduction');
            $io->success('Langues supportées définies à ["fr", "en"]');
        } else {
            $supportedLocales = $this->settingsManager->get('translation.supported_locales');
            $io->note('Langues supportées déjà configurées: ' . implode(', ', $supportedLocales));
        }

        $io->success('Paramètres de traduction initialisés avec succès !');

        return Command::SUCCESS;
    }
}