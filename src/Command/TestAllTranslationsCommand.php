<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:test-all-translations',
    description: 'Teste toutes les traductions des modules',
)]
class TestAllTranslationsCommand extends Command
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de Toutes les Traductions');

        $modules = [
            'admin' => ['dashboard.title', 'actions.create', 'status.active'],
            'userplus' => ['title', 'users.add_user', 'actions.edit'],
            'pages' => ['title', 'create.title', 'fields.title'],
            'articles' => ['title', 'create.title', 'fields.title'],
            'events' => ['title', 'create.title', 'fields.title'],
            'galleries' => ['title', 'create.title', 'fields.name'],
            'services' => ['title', 'create.title', 'fields.name'],
            'registration' => ['title', 'form.title', 'form.email'],
            'security' => ['login.title', 'profile.title', 'errors.access_denied']
        ];

        foreach ($modules as $domain => $keys) {
            $io->section("Module: $domain");
            
            foreach (['fr', 'en'] as $locale) {
                $io->writeln("Langue: $locale");
                
                foreach ($keys as $key) {
                    $translated = $this->translator->trans($key, [], $domain, $locale);
                    $io->writeln("  $key => $translated");
                }
                $io->newLine();
            }
        }

        $io->success('Test de toutes les traductions terminé !');

        return Command::SUCCESS;
    }
}