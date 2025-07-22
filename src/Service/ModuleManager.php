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
}