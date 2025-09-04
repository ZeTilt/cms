<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use App\Entity\Module;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class BaseFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // 1. Créer l'utilisateur admin par défaut
        $admin = new User();
        $admin->setEmail('admin@zetilt.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('ZeTilt');
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setIsVerified(true);
        $admin->setCreatedAt(new \DateTimeImmutable());
        
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'Admin123!');
        $admin->setPassword($hashedPassword);
        
        $manager->persist($admin);

        // 2. Activer les modules de base s'ils n'existent pas déjà
        $existingModules = [];
        $moduleRepo = $manager->getRepository(Module::class);
        $allModules = $moduleRepo->findAll();
        
        foreach ($allModules as $module) {
            $existingModules[] = $module->getName();
        }

        $modules = [
            ['name' => 'UserPlus', 'displayName' => 'Gestion Utilisateurs+', 'active' => true, 'description' => 'Gestion avancée des utilisateurs avec attributs dynamiques'],
            ['name' => 'Events', 'displayName' => 'Événements', 'active' => true, 'description' => 'Système de gestion d\'événements et réservations'],
            ['name' => 'Gallery', 'displayName' => 'Galeries', 'active' => true, 'description' => 'Galeries photos avec codes d\'accès et impression'],
            ['name' => 'Articles', 'displayName' => 'Articles/Blog', 'active' => true, 'description' => 'Système de blog et articles avec éditeur WYSIWYG'],
            ['name' => 'Business', 'displayName' => 'Fonctionnalités Business', 'active' => true, 'description' => 'Formulaires de contact et témoignages'],
            ['name' => 'Pages', 'displayName' => 'Pages Statiques', 'active' => true, 'description' => 'Gestion des pages statiques du site'],
        ];

        foreach ($modules as $moduleData) {
            // Ne créer le module que s'il n'existe pas déjà
            if (!in_array($moduleData['name'], $existingModules)) {
                $module = new Module();
                $module->setName($moduleData['name']);
                $module->setDisplayName($moduleData['displayName']);
                $module->setActive($moduleData['active']);
                $module->setDescription($moduleData['description']);
                
                $manager->persist($module);
            }
        }

        $manager->flush();
        
        echo "✅ Fixtures de base chargées :\n";
        echo "  - Utilisateur admin : admin@zetilt.com / Admin123!\n";
        echo "  - " . count($modules) . " modules configurés\n";
    }
}