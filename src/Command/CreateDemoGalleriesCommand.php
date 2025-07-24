<?php

namespace App\Command;

use App\Entity\Gallery;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:demo:create-galleries',
    description: 'Create demo galleries for photographer site',
)]
class CreateDemoGalleriesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Creating Demo Galleries for Photographer Site');

        // Get the admin user
        $admin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@zetilt.cms']);

        if (!$admin) {
            $io->error('Admin user not found. Please run zetilt:cms:init first.');
            return Command::FAILURE;
        }

        // Create demo galleries
        $this->createDemoGalleries($admin, $io);

        $this->entityManager->flush();

        $io->success('Demo galleries created successfully!');
        $io->note('Visit /galleries to see your photo galleries');
        $io->note('Visit /admin/galleries to manage them');

        return Command::SUCCESS;
    }

    private function createDemoGalleries(User $admin, SymfonyStyle $io): void
    {
        $io->section('Creating Demo Galleries');

        $galleries = [
            [
                'title' => 'Nature Photography',
                'slug' => 'nature-photography',
                'description' => 'Capturing the beauty of the natural world through landscapes, wildlife, and macro photography. From misty mountain peaks to intricate flower details, these images celebrate the diversity and wonder of nature.',
                'visibility' => 'public',
            ],
            [
                'title' => 'Portrait Sessions',
                'slug' => 'portrait-sessions',
                'description' => 'Professional portrait photography for individuals, couples, and families. Each session is carefully crafted to capture personality, emotion, and authentic moments in beautiful, timeless images.',
                'visibility' => 'public',
            ],
            [
                'title' => 'Wedding Photography',
                'slug' => 'wedding-photography',
                'description' => 'Documenting love stories through elegant, emotional wedding photography. From intimate ceremonies to grand celebrations, every precious moment is preserved with artistic vision and professional expertise.',
                'visibility' => 'public',
            ],
            [
                'title' => 'Urban Exploration',
                'slug' => 'urban-exploration',
                'description' => 'Discovering beauty in city landscapes, architecture, and street scenes. These images explore the contrast between modern urban life and timeless architectural elements.',
                'visibility' => 'public',
            ],
            [
                'title' => 'Client Gallery - Smith Wedding',
                'slug' => 'client-gallery-smith-wedding',
                'description' => 'Private gallery for the Smith family wedding. Access code required for viewing.',
                'visibility' => 'private',
                'access_code' => 'smith2024'
            ],
            [
                'title' => 'Commercial Shoot - Local Business',
                'slug' => 'commercial-shoot-local-business',
                'description' => 'Professional commercial photography for local business branding and marketing materials.',
                'visibility' => 'private',
                'access_code' => 'commercial123'
            ]
        ];

        foreach ($galleries as $galleryData) {
            $gallery = new Gallery();
            $gallery->setTitle($galleryData['title'])
                   ->setSlug($galleryData['slug'])
                   ->setDescription($galleryData['description'])
                   ->setVisibility($galleryData['visibility'])
                   ->setAuthor($admin);

            if ($galleryData['visibility'] === 'private' && isset($galleryData['access_code'])) {
                $gallery->setAccessCode($galleryData['access_code']);
            }

            $this->entityManager->persist($gallery);
            $io->writeln("✓ Created gallery: {$galleryData['title']} ({$galleryData['visibility']})");
        }
    }
}