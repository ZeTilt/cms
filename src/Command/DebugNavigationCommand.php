<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:debug-navigation',
    description: 'Debug navigation translations',
)]
class DebugNavigationCommand extends Command
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Debug Navigation Translations');

        // Test avec les bonnes clés
        $keys = [
            'navigation.dashboard',
            'navigation.users', 
            'navigation.view_site',
            'modules.title'
        ];

        foreach (['fr', 'en'] as $locale) {
            $io->section("Langue: $locale");
            foreach ($keys as $key) {
                $translated = $this->translator->trans($key, [], 'admin', $locale);
                $io->writeln("$key => $translated");
            }
        }

        $io->success('Debug terminé !');

        return Command::SUCCESS;
    }
}