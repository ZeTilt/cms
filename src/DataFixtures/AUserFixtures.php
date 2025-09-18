<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AUserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'email' => 'superadmin@venetes-plongee.fr',
                'firstName' => 'Super',
                'lastName' => 'Admin',
                'roles' => ['ROLE_SUPER_ADMIN'],
                'password' => 'superadmin123',
                'status' => 'active',
                'emailVerified' => true
            ],
            [
                'email' => 'fabrice@dhuicque.fr',
                'firstName' => 'Fabrice',
                'lastName' => 'Dhuicque',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'admin123',
                'status' => 'active',
                'emailVerified' => true
            ],
            [
                'email' => 'laetitia.chapel@venetes-plongee.fr',
                'firstName' => 'Laetitia',
                'lastName' => 'Chapel',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'admin123',
                'status' => 'active',
                'emailVerified' => true
            ],
            [
                'email' => 'berengere.desplenaire@venetes-plongee.fr',
                'firstName' => 'Bérengère',
                'lastName' => 'Desplenaire',
                'roles' => ['ROLE_USER'],
                'password' => 'user123',
                'status' => 'active',
                'emailVerified' => true
            ]
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setRoles($userData['roles']);
            $user->setStatus($userData['status']);
            $user->setEmailVerified($userData['emailVerified']);
            $user->setActive(true);

            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $userData['password']
            );
            $user->setPassword($hashedPassword);

            $manager->persist($user);
        }

        $manager->flush();
    }
}