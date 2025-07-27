<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:test-navigation-keys',
    description: 'Test navigation translation keys',
)]
class TestNavigationKeysCommand extends Command
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test Navigation Keys');

        $tests = [
            ['breadcrumb.pages', 'pages'],
            ['breadcrumb.articles', 'articles'],
            ['breadcrumb.galleries', 'galleries'],
            ['breadcrumb.services', 'services'],
            ['breadcrumb.events', 'events'],
            ['breadcrumb.business', 'business'],
            ['title', 'registration']
        ];

        foreach (['fr', 'en'] as $locale) {
            $io->section("Langue: $locale");
            foreach ($tests as [$key, $domain]) {
                $translated = $this->translator->trans($key, [], $domain, $locale);
                $io->writeln("$domain.$key => $translated");
            }
        }

        $io->success('Test terminé !');

        return Command::SUCCESS;
    }
}