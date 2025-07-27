<?php

namespace App\Command;

use App\Service\TranslationManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-translation',
    description: 'Teste le système de traduction',
)]
class TestTranslationCommand extends Command
{
    public function __construct(
        private TranslationManager $translationManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test du Système de Traduction');

        // Tester les langues supportées
        $supportedLocales = $this->translationManager->getSupportedLocales();
        $io->section('Langues Supportées');
        $io->writeln('Langues configurées : ' . implode(', ', $supportedLocales));

        // Tester la langue par défaut
        $defaultLocale = $this->translationManager->getDefaultLocale();
        $io->section('Langue par Défaut');
        $io->writeln('Langue par défaut : ' . $defaultLocale);

        // Tester isLocaleSupported
        $io->section('Test de Validation des Langues');
        $testLocales = ['fr', 'en', 'es', 'de', 'invalid'];
        foreach ($testLocales as $locale) {
            $isSupported = $this->translationManager->isLocaleSupported($locale);
            $status = $isSupported ? '✅ Supportée' : '❌ Non supportée';
            $io->writeln("$locale : $status");
        }

        // Tester les noms de langues
        $io->section('Noms des Langues');
        $localeNames = $this->translationManager->getLocaleNames();
        foreach ($supportedLocales as $locale) {
            $name = $localeNames[$locale] ?? 'Inconnu';
            $io->writeln("$locale : $name");
        }

        $io->success('Test du système de traduction terminé !');

        return Command::SUCCESS;
    }
}