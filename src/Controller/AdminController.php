<?php

namespace App\Controller;

use App\Service\ModuleManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/modules/{moduleName}/toggle', name: 'admin_modules_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function toggleModule(string $moduleName, Request $request, ModuleManager $moduleManager): JsonResponse
    {
        $action = $request->request->get('action'); // 'activate' or 'deactivate'
        
        if (!in_array($action, ['activate', 'deactivate'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }

        $success = $action === 'activate' 
            ? $moduleManager->activateModule($moduleName)
            : $moduleManager->deactivateModule($moduleName);

        if ($success) {
            $module = $moduleManager->getModule($moduleName);
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Module %s %s successfully', $moduleName, $action === 'activate' ? 'activated' : 'deactivated'),
                'active' => $module->isActive()
            ]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Module not found'], 404);
    }
}