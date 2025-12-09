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

    // ========================================
    // Méthodes pour la gestion des CACI
    // ========================================

    /**
     * Utilisateurs avec CACI déclaré mais non encore vérifié par un DP
     * @return User[]
     */
    public function findCaciPendingVerification(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.active = :active')
            ->andWhere('u.medicalCertificateExpiry IS NOT NULL')
            ->andWhere('u.medicalCertificateExpiry >= :today')
            ->andWhere('u.medicalCertificateVerifiedAt IS NULL')
            ->setParameter('active', true)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('u.medicalCertificateExpiry', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Utilisateurs avec CACI expiré
     * @return User[]
     */
    public function findCaciExpired(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.active = :active')
            ->andWhere('u.medicalCertificateExpiry IS NOT NULL')
            ->andWhere('u.medicalCertificateExpiry < :today')
            ->setParameter('active', true)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('u.medicalCertificateExpiry', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Utilisateurs actifs sans CACI déclaré
     * @return User[]
     */
    public function findCaciMissing(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.active = :active')
            ->andWhere('u.status = :approved')
            ->andWhere('u.medicalCertificateExpiry IS NULL')
            ->setParameter('active', true)
            ->setParameter('approved', 'approved')
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les utilisateurs avec CACI valide (vérifié et non expiré)
     */
    public function countCaciValid(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.active = :active')
            ->andWhere('u.medicalCertificateExpiry IS NOT NULL')
            ->andWhere('u.medicalCertificateExpiry >= :today')
            ->andWhere('u.medicalCertificateVerifiedAt IS NOT NULL')
            ->setParameter('active', true)
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Utilisateurs avec CACI expirant dans les X prochains jours
     * @return User[]
     */
    public function findCaciExpiringSoon(int $days = 30): array
    {
        $today = new \DateTime('today');
        $futureDate = (new \DateTime('today'))->modify("+{$days} days");

        return $this->createQueryBuilder('u')
            ->andWhere('u.active = :active')
            ->andWhere('u.medicalCertificateExpiry IS NOT NULL')
            ->andWhere('u.medicalCertificateExpiry >= :today')
            ->andWhere('u.medicalCertificateExpiry <= :futureDate')
            ->setParameter('active', true)
            ->setParameter('today', $today)
            ->setParameter('futureDate', $futureDate)
            ->orderBy('u.medicalCertificateExpiry', 'ASC')
            ->getQuery()
            ->getResult();
    }

}