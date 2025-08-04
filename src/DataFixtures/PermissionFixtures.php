<?php

namespace App\DataFixtures;

use App\Entity\Permission;
use App\Service\PermissionService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PermissionFixtures extends Fixture
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Utiliser le service pour synchroniser les permissions
        $syncedCount = $this->permissionService->syncPermissions();
        
        // Les permissions sont créées uniquement par le service de synchronisation

        $manager->flush();
    }
}