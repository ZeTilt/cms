<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:test-pages-keys',
    description: 'Test pages translation keys',
)]
class TestPagesKeysCommand extends Command
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test Pages Keys');

        $keys = [
            'list.title',
            'subtitle',
            'create.title'
        ];

        foreach (['fr', 'en'] as $locale) {
            $io->section("Langue: $locale");
            foreach ($keys as $key) {
                $translated = $this->translator->trans($key, [], 'pages', $locale);
                $io->writeln("$key => $translated");
            }
        }

        $io->success('Test terminé !');

        return Command::SUCCESS;
    }
}