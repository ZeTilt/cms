<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Module;

class ModuleManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function activateModule(string $moduleName): bool
    {
        $module = $this->getModule($moduleName);

        if (!$module) {
            return false;
        }

        // Vérifier les dépendances
        $dependencies = $this->getModuleDependencies($moduleName);
        foreach ($dependencies as $dependency) {
            if (!$this->isModuleActive($dependency)) {
                throw new \RuntimeException("Le module '{$moduleName}' nécessite que le module '{$dependency}' soit activé.");
            }
        }

        $module->setActive(true);
        $this->entityManager->flush();

        return true;
    }

    public function deactivateModule(string $moduleName): bool
    {
        $module = $this->getModule($moduleName);

        if (!$module) {
            return false;
        }

        // Vérifier si d'autres modules dépendent de celui-ci
        $dependentModules = $this->getModulesDependingOn($moduleName);
        if (!empty($dependentModules)) {
            $moduleNames = array_map(fn($m) => $m->getDisplayName(), $dependentModules);
            throw new \RuntimeException("Impossible de désactiver le module '{$moduleName}' car les modules suivants en dépendent : " . implode(', ', $moduleNames));
        }

        $module->setActive(false);
        $this->entityManager->flush();

        return true;
    }

    public function isModuleActive(string $moduleName): bool
    {
        $module = $this->getModule($moduleName);
        return $module && $module->isActive();
    }

    public function getActiveModules(): array
    {
        return $this->entityManager->getRepository(Module::class)
            ->findBy(['active' => true]);
    }

    public function getAllModules(): array
    {
        return $this->entityManager->getRepository(Module::class)
            ->findAll();
    }

    public function registerModule(string $name, string $displayName, ?string $description = null, array $config = []): Module
    {
        $existingModule = $this->getModule($name);

        if ($existingModule) {
            // Update existing module
            $existingModule->setDisplayName($displayName);
            $existingModule->setDescription($description);
            $existingModule->setConfig($config);

            $this->entityManager->flush();
            return $existingModule;
        }

        // Create new module
        $module = new Module();
        $module->setName($name);
        $module->setDisplayName($displayName);
        $module->setDescription($description);
        $module->setConfig($config);

        $this->entityManager->persist($module);
        $this->entityManager->flush();

        return $module;
    }

    public function getModule(string $name): ?Module
    {
        return $this->entityManager->getRepository(Module::class)
            ->findOneBy(['name' => $name]);
    }

    public function getModuleConfig(string $moduleName): array
    {
        $module = $this->getModule($moduleName);
        return $module ? $module->getConfig() : [];
    }

    public function updateModuleConfig(string $moduleName, array $config): bool
    {
        $module = $this->getModule($moduleName);

        if (!$module) {
            return false;
        }

        $module->setConfig($config);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Obtenir les dépendances d'un module
     */
    public function getModuleDependencies(string $moduleName): array
    {
        // Configuration des dépendances hardcodées pour simplifier
        $dependencies = [
            'shop' => ['gallery'], // Le module shop dépend du module gallery
            'image_security' => ['gallery'], // La sécurité d'images dépend des galeries
        ];

        return $dependencies[$moduleName] ?? [];
    }

    /**
     * Obtenir les modules qui dépendent d'un module donné
     */
    public function getModulesDependingOn(string $moduleName): array
    {
        $dependentModules = [];
        $allModules = $this->getAllModules();

        foreach ($allModules as $module) {
            if ($module->isActive()) {
                $dependencies = $this->getModuleDependencies($module->getName());
                if (in_array($moduleName, $dependencies)) {
                    $dependentModules[] = $module;
                }
            }
        }

        return $dependentModules;
    }

    /**
     * Vérifier si un module peut être activé (dépendances satisfaites)
     */
    public function canActivateModule(string $moduleName): array
    {
        $errors = [];
        $dependencies = $this->getModuleDependencies($moduleName);
        
        foreach ($dependencies as $dependency) {
            if (!$this->isModuleActive($dependency)) {
                $depModule = $this->getModule($dependency);
                $depName = $depModule ? $depModule->getDisplayName() : $dependency;
                $errors[] = "Le module '{$depName}' doit être activé en premier.";
            }
        }

        return $errors;
    }
}
