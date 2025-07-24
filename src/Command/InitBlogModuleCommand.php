<?php

namespace App\Command;

use App\Service\ModuleManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-blog-module',
    description: 'Initialize the Blog module in the CMS',
)]
class InitBlogModuleCommand extends Command
{
    public function __construct(
        private ModuleManager $moduleManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->moduleManager->registerModule(
                'blog',
                'Blog Management',
                'Manage blog articles with WYSIWYG editor, categories and tags',
                [
                    'allow_comments' => false,
                    'posts_per_page' => 10,
                    'enable_categories' => true,
                    'enable_tags' => true,
                    'enable_featured_images' => true
                ]
            );

            $io->success('Blog module has been registered successfully!');
            $io->note('You can now activate it from the Super Admin interface.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to register Blog module: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}