<?php

namespace App\Twig;

use App\Service\ModuleManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ModuleExtension extends AbstractExtension
{
    public function __construct(
        private ModuleManager $moduleManager
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_module_active', [$this, 'isModuleActive']),
        ];
    }

    public function isModuleActive(string $moduleName): bool
    {
        return $this->moduleManager->isModuleActive($moduleName);
    }
}