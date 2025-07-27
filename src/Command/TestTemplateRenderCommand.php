<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

#[AsCommand(
    name: 'app:test-template-render',
    description: 'Teste le rendu des templates avec traductions',
)]
class TestTemplateRenderCommand extends Command
{
    public function __construct(
        private Environment $twig
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test du Rendu des Templates avec Traductions');

        // Test direct de traduction simple
        $template = $this->twig->createTemplate('{{ "userplus.title"|trans({}, "userplus") }}');
        $result = $template->render();
        
        $io->section('Test de Template Simple');
        $io->writeln("Rendu: '$result'");

        // Test avec plusieurs traductions
        $template2 = $this->twig->createTemplate('
            <ul>
                <li>{{ "userplus.title"|trans({}, "userplus") }}</li>
                <li>{{ "userplus.users.add_user"|trans({}, "userplus") }}</li>
                <li>{{ "userplus.actions.edit"|trans({}, "userplus") }}</li>
            </ul>
        ');
        $result2 = $template2->render();
        
        $io->section('Test de Template Multiple');
        $io->writeln("Rendu:");
        $io->writeln($result2);

        $io->success('Test du rendu des templates terminé !');

        return Command::SUCCESS;
    }
}