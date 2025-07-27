<?php

namespace App\Command;

use App\Service\TranslationManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:configure-translation',
    description: 'Configure le système de traduction avec plusieurs langues',
)]
class ConfigureTranslationCommand extends Command
{
    public function __construct(
        private TranslationManager $translationManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Configuration du Système de Traduction');

        // Configurer les langues supportées
        $supportedLocales = ['fr', 'en'];
        $this->translationManager->setSupportedLocales($supportedLocales);
        
        // Configurer la langue par défaut
        $this->translationManager->setDefaultLocale('fr');
        
        $io->success('Configuration terminée !');
        $io->writeln('Langues supportées : ' . implode(', ', $supportedLocales));
        $io->writeln('Langue par défaut : fr');

        return Command::SUCCESS;
    }
}