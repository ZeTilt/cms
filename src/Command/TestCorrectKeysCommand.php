<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:test-correct-keys',
    description: 'Teste les vraies clés de traduction UserPlus',
)]
class TestCorrectKeysCommand extends Command
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test des Vraies Clés UserPlus');

        // Test des vraies clés qui existent dans le fichier
        $testKeys = [
            'title',
            'users.title',
            'users.add_user',
            'actions.edit',
            'status.active'
        ];

        foreach (['fr', 'en'] as $locale) {
            $io->section("Langue: $locale");
            
            foreach ($testKeys as $key) {
                $translated = $this->translator->trans($key, [], 'userplus', $locale);
                $io->writeln("$key => $translated");
            }
        }

        $io->success('Test des clés correctes terminé !');

        return Command::SUCCESS;
    }
}