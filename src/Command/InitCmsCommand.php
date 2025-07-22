<?php

namespace App\Command;

use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

#[AsCommand(
    name: 'zetilt:cms:init',
    description: 'Initialize CMS with basic modules and admin user',
)]
class InitCmsCommand extends Command
{
    public function __construct(
        private ModuleManager $moduleManager,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('ZeTilt CMS Initialization');

        // Register core modules
        $this->registerCoreModules($io);

        // Create super admin user
        $this->createSuperAdminUser($io);

        $io->success('ZeTilt CMS has been initialized successfully!');

        return Command::SUCCESS;
    }

    private function registerCoreModules(SymfonyStyle $io): void
    {
        $io->section('Registering Core Modules');

        $modules = [
            ['pages', 'Pages Module', 'Static pages and blog functionality'],
            ['gallery', 'Gallery Module', 'Image galleries and media management'],
            ['userplus', 'UserPlus Module', 'Extended user management with custom attributes'],
            ['events', 'Events Module', 'Calendar and event management'],
            ['business', 'Business Module', 'Business-specific customizations'],
        ];

        foreach ($modules as [$name, $displayName, $description]) {
            $this->moduleManager->registerModule($name, $displayName, $description);
            $io->writeln(sprintf('✓ Registered module: <info>%s</info>', $displayName));
        }
    }

    private function createSuperAdminUser(SymfonyStyle $io): void
    {
        $io->section('Creating Super Admin User');

        // Check if super admin already exists
        $existingAdmin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@zetilt.cms']);

        if ($existingAdmin) {
            $io->note('Super admin user already exists.');
            return;
        }

        $user = new User();
        $user->setEmail('admin@zetilt.cms');
        $user->setFirstName('Super');
        $user->setLastName('Admin');
        $user->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, 'admin123');
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->writeln('✓ Created super admin user:');
        $io->writeln('   Email: <info>admin@zetilt.cms</info>');
        $io->writeln('   Password: <comment>admin123</comment>');
        $io->note('Please change the default password after first login.');
    }
}