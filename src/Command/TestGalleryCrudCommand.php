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
    name: 'zetilt:test:gallery-crud',
    description: 'Test gallery CRUD operations',
)]
class TestGalleryCrudCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing Gallery CRUD Operations');

        // Get admin user
        $admin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@zetilt.cms']);

        if (!$admin) {
            $io->error('Admin user not found');
            return Command::FAILURE;
        }

        $io->success('✓ Admin user found: ' . $admin->getFullName());

        // Test Create
        $gallery = new Gallery();
        $gallery->setTitle('Test Gallery ' . date('H:i:s'));
        $gallery->setDescription('Test gallery for CRUD testing');
        $gallery->setVisibility('public');
        $gallery->setAuthor($admin);

        $this->entityManager->persist($gallery);
        $this->entityManager->flush();

        $io->success('✓ Gallery created with ID: ' . $gallery->getId());

        // Test Read
        $foundGallery = $this->entityManager->getRepository(Gallery::class)
            ->find($gallery->getId());

        if ($foundGallery) {
            $io->success('✓ Gallery found: ' . $foundGallery->getTitle());
        } else {
            $io->error('✗ Gallery not found');
            return Command::FAILURE;
        }

        // Test Update
        $foundGallery->setTitle('Updated Test Gallery');
        $this->entityManager->flush();

        $io->success('✓ Gallery updated');

        // Test Delete
        $this->entityManager->remove($foundGallery);
        $this->entityManager->flush();

        $io->success('✓ Gallery deleted');

        // Verify deletion
        $deletedGallery = $this->entityManager->getRepository(Gallery::class)
            ->find($gallery->getId());

        if (!$deletedGallery) {
            $io->success('✓ Gallery deletion confirmed');
        } else {
            $io->error('✗ Gallery still exists after deletion');
            return Command::FAILURE;
        }

        $io->success('All CRUD operations completed successfully!');
        return Command::SUCCESS;
    }
}