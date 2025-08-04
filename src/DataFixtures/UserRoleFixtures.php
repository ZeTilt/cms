<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserRoleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer les rôles
        $roleRepo = $manager->getRepository(Role::class);
        $userRepo = $manager->getRepository(User::class);
        
        $superAdminRole = $roleRepo->findOneBy(['name' => 'ROLE_SUPER_ADMIN']);
        $adminRole = $roleRepo->findOneBy(['name' => 'ROLE_ADMIN']);
        $directeurRole = $roleRepo->findOneBy(['name' => 'ROLE_DIRECTEUR_PLONGEE']);
        $piloteRole = $roleRepo->findOneBy(['name' => 'ROLE_PILOTE']);
        $plongeurRole = $roleRepo->findOneBy(['name' => 'ROLE_PLONGEUR']);
        
        // Récupérer quelques utilisateurs
        $users = $userRepo->findAll();
        
        if (count($users) < 10) {
            return; // Pas assez d'utilisateurs
        }
        
        // Attribuer le rôle SuperAdmin au premier utilisateur (superadmin@zetilt.fr)
        if ($superAdminRole && isset($users[0])) {
            $userRole = new UserRole();
            $userRole->setUser($users[0]);
            $userRole->setRole($superAdminRole);
            $userRole->setAssignedBy($users[0]); // Auto-assigné
            $manager->persist($userRole);
        }
        
        // Attribuer le rôle Admin au deuxième utilisateur (admin@zetilt.fr)
        if ($adminRole && isset($users[1])) {
            $userRole = new UserRole();
            $userRole->setUser($users[1]);
            $userRole->setRole($adminRole);
            $userRole->setAssignedBy($users[0]); // Assigné par le super admin
            $manager->persist($userRole);
        }
        
        // Attribuer le rôle Directeur de Plongée à 2 utilisateurs
        if ($directeurRole) {
            for ($i = 2; $i < 4 && $i < count($users); $i++) {
                $userRole = new UserRole();
                $userRole->setUser($users[$i]);
                $userRole->setRole($directeurRole);
                $userRole->setAssignedBy($users[1]); // Assigné par l'admin
                $manager->persist($userRole);
            }
        }
        
        // Attribuer le rôle Pilote à 5 utilisateurs
        if ($piloteRole) {
            for ($i = 4; $i < 9 && $i < count($users); $i++) {
                $userRole = new UserRole();
                $userRole->setUser($users[$i]);
                $userRole->setRole($piloteRole);
                $userRole->setAssignedBy($users[2]); // Assigné par un directeur
                $manager->persist($userRole);
            }
        }
        
        // Attribuer le rôle Plongeur à 10 utilisateurs
        if ($plongeurRole) {
            for ($i = 9; $i < 19 && $i < count($users); $i++) {
                $userRole = new UserRole();
                $userRole->setUser($users[$i]);
                $userRole->setRole($plongeurRole);
                $userRole->setAssignedBy($users[3]); // Assigné par un directeur
                $manager->persist($userRole);
            }
        }
        
        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
            RoleFixtures::class,
        ];
    }
}