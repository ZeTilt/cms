<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:test-final-translations',
    description: 'Test final des traductions complètes',
)]
class TestFinalTranslationsCommand extends Command
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test Final du Système de Traduction Complet');

        // Test navigation
        $io->section('Navigation Admin');
        foreach (['fr', 'en'] as $locale) {
            $io->writeln("Langue: $locale");
            $io->writeln("  Dashboard: " . $this->translator->trans('admin.navigation.dashboard', [], 'admin', $locale));
            $io->writeln("  Users: " . $this->translator->trans('admin.navigation.users', [], 'admin', $locale));
            $io->writeln("  View Site: " . $this->translator->trans('admin.navigation.view_site', [], 'admin', $locale));
        }

        // Test modules principaux
        $modules = [
            'userplus' => 'title',
            'pages' => 'title', 
            'events' => 'title',
            'articles' => 'title',
            'galleries' => 'title',
            'services' => 'title',
            'business' => 'title'
        ];

        $io->section('Modules Principaux');
        foreach ($modules as $domain => $key) {
            $fr = $this->translator->trans($key, [], $domain, 'fr');
            $en = $this->translator->trans($key, [], $domain, 'en');
            $io->writeln("$domain: FR='$fr' | EN='$en'");
        }

        $io->success('Test complet terminé !');

        return Command::SUCCESS;
    }
}