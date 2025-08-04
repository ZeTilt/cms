<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Super Administrateur (niveau 100)
        $roleSuperAdmin = new Role();
        $roleSuperAdmin->setName('ROLE_SUPER_ADMIN');
        $roleSuperAdmin->setDisplayName('Super Administrateur');
        $roleSuperAdmin->setDescription('Accès complet au système avec gestion des modules et configurations');
        $roleSuperAdmin->setHierarchy(100);
        $roleSuperAdmin->setPermissions([
            'ADMIN_FULL',
            'MODULE_MANAGE',
            'USER_MANAGE',
            'ROLE_MANAGE',
            'SYSTEM_CONFIG'
        ]);
        $manager->persist($roleSuperAdmin);

        // Administrateur (niveau 80)
        $roleAdmin = new Role();
        $roleAdmin->setName('ROLE_ADMIN');
        $roleAdmin->setDisplayName('Administrateur');
        $roleAdmin->setDescription('Gestion complète du site et des utilisateurs');
        $roleAdmin->setHierarchy(80);
        $roleAdmin->setPermissions([
            'ADMIN_ACCESS',
            'USER_MANAGE',
            'CONTENT_MANAGE',
            'EVENT_MANAGE',
            'GALLERY_MANAGE'
        ]);
        $manager->persist($roleAdmin);

        // Directeur de Plongée (niveau 60)
        $roleDirecteurPlongee = new Role();
        $roleDirecteurPlongee->setName('ROLE_DIRECTEUR_PLONGEE');
        $roleDirecteurPlongee->setDisplayName('Directeur de Plongée');
        $roleDirecteurPlongee->setDescription('Gestion des plongées, formations et certifications');
        $roleDirecteurPlongee->setHierarchy(60);
        $roleDirecteurPlongee->setPermissions([
            'DIVE_MANAGE',
            'TRAINING_MANAGE',
            'CERTIFICATION_MANAGE',
            'MEMBER_MANAGE',
            'EVENT_MODERATE'
        ]);
        $manager->persist($roleDirecteurPlongee);

        // Pilote/Encadrant (niveau 40)
        $rolePilote = new Role();
        $rolePilote->setName('ROLE_PILOTE');
        $rolePilote->setDisplayName('Pilote/Encadrant');
        $rolePilote->setDescription('Encadrement des plongées et gestion des groupes');
        $rolePilote->setHierarchy(40);
        $rolePilote->setPermissions([
            'DIVE_LEAD',
            'GROUP_MANAGE',
            'SAFETY_MANAGE',
            'EQUIPMENT_CHECK'
        ]);
        $manager->persist($rolePilote);

        // Plongeur (niveau 20)
        $rolePlongeur = new Role();
        $rolePlongeur->setName('ROLE_PLONGEUR');
        $rolePlongeur->setDisplayName('Plongeur');
        $rolePlongeur->setDescription('Participation aux activités et accès aux services membres');
        $rolePlongeur->setHierarchy(20);
        $rolePlongeur->setPermissions([
            'DIVE_PARTICIPATE',
            'EVENT_REGISTER',
            'PROFILE_MANAGE',
            'GALLERY_ACCESS'
        ]);
        $manager->persist($rolePlongeur);

        $manager->flush();
    }
}