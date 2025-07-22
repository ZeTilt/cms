<?php

namespace App\Controller;

use App\Service\ModuleManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(ModuleManager $moduleManager): Response
    {
        $activeModules = $moduleManager->getActiveModules();
        
        return $this->render('admin/dashboard.html.twig', [
            'modules' => $activeModules,
        ]);
    }

    #[Route('/modules', name: 'admin_modules')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function modules(ModuleManager $moduleManager): Response
    {
        $allModules = $moduleManager->getAllModules();
        
        return $this->render('admin/modules.html.twig', [
            'modules' => $allModules,
        ]);
    }
}