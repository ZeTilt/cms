<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.name', 'ASC')
            ->addOrderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[] Returns an array of User objects with admin roles
     */
    public function findAdmins(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role_admin OR u.roles LIKE :role_super_admin')
            ->setParameter('role_admin', '%"ROLE_ADMIN"%')
            ->setParameter('role_super_admin', '%"ROLE_SUPER_ADMIN"%')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return User[] Returns users created after a certain date
     */
    public function findRecentUsers(int $days = 30): array
    {
        $date = new \DateTime("-{$days} days");

        return $this->createQueryBuilder('u')
            ->andWhere('u.createdAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[] Returns users who are pilots
     */
    public function findPilots(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isPilot = :true')
            ->andWhere('u.active = :active')
            ->setParameter('true', true)
            ->setParameter('active', true)
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[] Returns users who can be diving directors (admins + instructors)
     */
    public function findDivingDirectors(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.active = :active')
            ->andWhere('u.roles LIKE :role_admin OR u.roles LIKE :role_super_admin')
            ->setParameter('active', true)
            ->setParameter('role_admin', '%"ROLE_ADMIN"%')
            ->setParameter('role_super_admin', '%"ROLE_SUPER_ADMIN"%')
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

}