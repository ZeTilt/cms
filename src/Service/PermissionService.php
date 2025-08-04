<?php

namespace App\Service;

use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;

class PermissionService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Définitions des modules et leurs actions CRUD
     */
    public function getModuleDefinitions(): array
    {
        return [
            'admin' => [
                'name' => 'Administration',
                'actions' => ['access', 'config', 'logs', 'backup']
            ],
            'user' => [
                'name' => 'Utilisateurs', 
                'actions' => ['list', 'create', 'edit', 'delete', 'manage']
            ],
            'role' => [
                'name' => 'Rôles',
                'actions' => ['list', 'create', 'edit', 'delete', 'assign']
            ],
            'content' => [
                'name' => 'Pages/Articles',
                'actions' => ['list', 'create', 'edit', 'delete', 'publish']
            ],
            'gallery' => [
                'name' => 'Galeries',
                'actions' => ['list', 'create', 'edit', 'delete', 'upload', 'access']
            ],
            'event' => [
                'name' => 'Événements',
                'actions' => ['list', 'create', 'edit', 'delete', 'register', 'moderate']
            ],
            'system' => [
                'name' => 'Système',
                'actions' => ['translate', 'module', 'cache', 'maintenance']
            ]
        ];
    }

    /**
     * Synchronise les permissions avec les définitions des modules
     */
    public function syncPermissions(): int
    {
        $created = 0;
        $modules = $this->getModuleDefinitions();
        
        foreach ($modules as $moduleKey => $moduleData) {
            foreach ($moduleData['actions'] as $action) {
                $permissionName = strtoupper($moduleKey . '_' . $action);
                
                // Vérifier si la permission existe déjà
                $existingPermission = $this->entityManager
                    ->getRepository(Permission::class)
                    ->findOneBy(['name' => $permissionName]);
                
                if (!$existingPermission) {
                    $permission = new Permission();
                    $permission->setName($permissionName);
                    $permission->setDisplayName($this->getActionDisplayName($action) . ' - ' . $moduleData['name']);
                    $permission->setDescription($this->getPermissionDescription($moduleKey, $action));
                    $permission->setModule($moduleKey);
                    $permission->setAction($action);
                    
                    $this->entityManager->persist($permission);
                    $created++;
                }
            }
        }
        
        if ($created > 0) {
            $this->entityManager->flush();
        }
        
        return $created;
    }

    /**
     * Obtient le nom d'affichage d'une action
     */
    private function getActionDisplayName(string $action): string
    {
        return match($action) {
            'list' => 'Consulter',
            'create' => 'Créer', 
            'edit' => 'Modifier',
            'delete' => 'Supprimer',
            'access' => 'Accéder',
            'manage' => 'Gérer',
            'assign' => 'Attribuer',
            'publish' => 'Publier',
            'upload' => 'Télécharger',
            'register' => 'S\'inscrire',
            'moderate' => 'Modérer',
            'config' => 'Configurer',
            'logs' => 'Logs',
            'backup' => 'Sauvegarde',
            'translate' => 'Traduire',
            'module' => 'Modules',
            'cache' => 'Cache',
            'maintenance' => 'Maintenance',
            default => ucfirst($action)
        };
    }

    /**
     * Obtient la description d'une permission
     */
    private function getPermissionDescription(string $module, string $action): string
    {
        $moduleNames = [
            'admin' => 'administration',
            'user' => 'utilisateurs',
            'role' => 'rôles',
            'content' => 'contenu',
            'gallery' => 'galeries',
            'event' => 'événements',
            'system' => 'système'
        ];
        
        $actionNames = [
            'list' => 'consulter la liste des',
            'create' => 'créer de nouveaux',
            'edit' => 'modifier les',
            'delete' => 'supprimer les',
            'access' => 'accéder aux',
            'manage' => 'gérer les',
            'assign' => 'attribuer les',
            'publish' => 'publier le',
            'upload' => 'télécharger dans les',
            'register' => 's\'inscrire aux',
            'moderate' => 'modérer les',
            'config' => 'configurer l\'',
            'logs' => 'consulter les logs de l\'',
            'backup' => 'effectuer des sauvegardes de l\'',
            'translate' => 'traduire le',
            'module' => 'gérer les modules du',
            'cache' => 'gérer le cache du',
            'maintenance' => 'effectuer la maintenance du'
        ];
        
        $moduleName = $moduleNames[$module] ?? $module;
        $actionName = $actionNames[$action] ?? $action;
        
        return "Permet de {$actionName} {$moduleName}";
    }

    /**
     * Obtient toutes les permissions groupées par module
     */
    public function getPermissionsByModule(): array
    {
        $permissions = $this->entityManager
            ->getRepository(Permission::class)
            ->findBy(['active' => true], ['module' => 'ASC', 'action' => 'ASC']);
        
        $grouped = [];
        foreach ($permissions as $permission) {
            $grouped[$permission->getModule()][] = $permission;
        }
        
        return $grouped;
    }
}