<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Create admin user',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'User email', 'superadmin@zetilt.fr')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password', 'admin123')
            ->addArgument('firstName', InputArgument::OPTIONAL, 'First name', 'Super')
            ->addArgument('lastName', InputArgument::OPTIONAL, 'Last name', 'Admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->warning("User {$email} already exists. Updating password...");
            $hashedPassword = $this->passwordHasher->hashPassword($existingUser, $password);
            $existingUser->setPassword($hashedPassword);
            $this->entityManager->flush();
            $io->success("Password updated for {$email}: {$password}");
            return Command::SUCCESS;
        }

        // Create new user
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success("Admin user created successfully!");
        $io->table(['Field', 'Value'], [
            ['Email', $email],
            ['Password', $password],
            ['Name', "$firstName $lastName"],
            ['Roles', 'ROLE_SUPER_ADMIN']
        ]);

        return Command::SUCCESS;
    }
}