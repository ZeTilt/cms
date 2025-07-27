<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:test-translation-resolver',
    description: 'Teste le résolveur de traductions Symfony',
)]
class TestTranslationResolverCommand extends Command
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test du Résolveur de Traductions Symfony');

        // Test des traductions avec domaine userplus
        $io->section('Test avec Domaine userplus');
        
        $testKeys = [
            'userplus.title',
            'userplus.users.add_user',
            'userplus.actions.edit',
            'userplus.status.active'
        ];

        foreach ($testKeys as $key) {
            $translated = $this->translator->trans($key, [], 'userplus', 'fr');
            $io->writeln("$key => $translated");
        }

        // Test sans domaine spécifique
        $io->section('Test avec Domaine par défaut');
        
        foreach ($testKeys as $key) {
            $translated = $this->translator->trans($key, [], null, 'fr');
            $io->writeln("$key => $translated");
        }

        // Test en anglais
        $io->section('Test en Anglais');
        
        foreach ($testKeys as $key) {
            $translated = $this->translator->trans($key, [], 'userplus', 'en');
            $io->writeln("$key => $translated");
        }

        $io->success('Test du résolveur de traductions terminé !');

        return Command::SUCCESS;
    }
}