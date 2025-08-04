<?php

namespace App\DataFixtures;

use App\Entity\Permission;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class NewRoleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer toutes les permissions
        $permissions = $manager->getRepository(Permission::class)->findAll();
        $permissionsByName = [];
        foreach ($permissions as $permission) {
            $permissionsByName[$permission->getName()] = $permission;
        }

        // Super Administrateur (niveau 100)
        $roleSuperAdmin = $this->findOrCreateRole($manager, 'ROLE_SUPER_ADMIN');
        $roleSuperAdmin->setDisplayName('Super Administrateur');
        $roleSuperAdmin->setDescription('Accès complet au système avec gestion des modules et configurations');
        $roleSuperAdmin->setHierarchy(100);
        
        // Super Admin a toutes les permissions
        foreach ($permissions as $permission) {
            $roleSuperAdmin->addPermission($permission);
        }
        $manager->persist($roleSuperAdmin);

        // Administrateur (niveau 80)
        $roleAdmin = $this->findOrCreateRole($manager, 'ROLE_ADMIN');
        $roleAdmin->setDisplayName('Administrateur');
        $roleAdmin->setDescription('Gestion complète du site et des utilisateurs');
        $roleAdmin->setHierarchy(80);
        
        $adminPermissions = [
            'ADMIN_ACCESS', 'USER_LIST', 'USER_CREATE', 'USER_EDIT', 'USER_MANAGE',
            'CONTENT_LIST', 'CONTENT_CREATE', 'CONTENT_EDIT', 'CONTENT_DELETE', 'CONTENT_PUBLISH',
            'EVENT_LIST', 'EVENT_CREATE', 'EVENT_EDIT', 'EVENT_DELETE', 'EVENT_MODERATE',
            'GALLERY_LIST', 'GALLERY_CREATE', 'GALLERY_EDIT', 'GALLERY_DELETE', 'GALLERY_UPLOAD'
        ];
        
        foreach ($adminPermissions as $permName) {
            if (isset($permissionsByName[$permName])) {
                $roleAdmin->addPermission($permissionsByName[$permName]);
            }
        }
        $manager->persist($roleAdmin);

        // Modérateur (niveau 60)
        $roleModerator = $this->findOrCreateRole($manager, 'ROLE_MODERATOR');
        $roleModerator->setDisplayName('Modérateur');
        $roleModerator->setDescription('Modération du contenu et gestion des utilisateurs');
        $roleModerator->setHierarchy(60);
        
        $moderatorPermissions = [
            'USER_LIST', 'USER_EDIT',
            'CONTENT_LIST', 'CONTENT_EDIT', 'CONTENT_DELETE',
            'EVENT_LIST', 'EVENT_CREATE', 'EVENT_EDIT', 'EVENT_MODERATE',
            'GALLERY_LIST', 'GALLERY_ACCESS'
        ];
        
        foreach ($moderatorPermissions as $permName) {
            if (isset($permissionsByName[$permName])) {
                $roleModerator->addPermission($permissionsByName[$permName]);
            }
        }
        $manager->persist($roleModerator);

        // Éditeur (niveau 40)
        $roleEditor = $this->findOrCreateRole($manager, 'ROLE_EDITOR');
        $roleEditor->setDisplayName('Éditeur');
        $roleEditor->setDescription('Création et édition de contenu');
        $roleEditor->setHierarchy(40);
        
        $editorPermissions = [
            'CONTENT_LIST', 'CONTENT_CREATE', 'CONTENT_EDIT',
            'EVENT_LIST', 'EVENT_CREATE', 'EVENT_EDIT',
            'GALLERY_LIST', 'GALLERY_CREATE', 'GALLERY_EDIT'
        ];
        
        foreach ($editorPermissions as $permName) {
            if (isset($permissionsByName[$permName])) {
                $roleEditor->addPermission($permissionsByName[$permName]);
            }
        }
        $manager->persist($roleEditor);

        // Membre (niveau 20)
        $roleMember = $this->findOrCreateRole($manager, 'ROLE_MEMBER');
        $roleMember->setDisplayName('Membre');
        $roleMember->setDescription('Accès aux services membres et participation aux activités');
        $roleMember->setHierarchy(20);
        
        $memberPermissions = [
            'EVENT_LIST', 'EVENT_REGISTER', 'GALLERY_ACCESS'
        ];
        
        foreach ($memberPermissions as $permName) {
            if (isset($permissionsByName[$permName])) {
                $roleMember->addPermission($permissionsByName[$permName]);
            }
        }
        $manager->persist($roleMember);

        $manager->flush();
    }

    private function findOrCreateRole(ObjectManager $manager, string $name): Role
    {
        $role = $manager->getRepository(Role::class)->findOneBy(['name' => $name]);
        
        if (!$role) {
            $role = new Role();
            $role->setName($name);
        }
        
        return $role;
    }

    public function getDependencies(): array
    {
        return [
            PermissionFixtures::class,
        ];
    }
}