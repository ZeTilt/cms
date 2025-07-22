<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait WebTestTrait
{
    private EntityManagerInterface $entityManager;
    
    protected function getEntityManager(): EntityManagerInterface
    {
        if (!isset($this->entityManager)) {
            $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        }
        
        return $this->entityManager;
    }

    protected function createTestUser(
        string $email = 'test@example.com',
        array $roles = ['ROLE_ADMIN'],
        string $password = 'password123'
    ): User {
        $user = new User();
        $user->setEmail($email)
             ->setFirstName('Test')
             ->setLastName('User')
             ->setRoles($roles);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $user;
    }

    protected function cleanupUser(User $user): void
    {
        $em = $this->getEntityManager();
        $em->remove($user);
        $em->flush();
    }
}