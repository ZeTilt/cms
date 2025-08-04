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
        
        // Enrichir les modules avec les informations de dépendances
        $modulesWithDeps = [];
        foreach ($allModules as $module) {
            $moduleName = $module->getName();
            $dependencies = $moduleManager->getModuleDependencies($moduleName);
            $dependentModules = $moduleManager->getModulesDependingOn($moduleName);
            $canActivate = $moduleManager->canActivateModule($moduleName);
            
            $modulesWithDeps[] = [
                'module' => $module,
                'dependencies' => $dependencies,
                'dependent_modules' => $dependentModules,
                'can_activate' => empty($canActivate),
                'activation_errors' => $canActivate
            ];
        }
        
        return $this->render('admin/modules.html.twig', [
            'modules' => $allModules,
            'modules_with_dependencies' => $modulesWithDeps,
        ]);
    }

    #[Route('/modules/{moduleName}/toggle', name: 'admin_modules_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function toggleModule(string $moduleName, Request $request, ModuleManager $moduleManager): JsonResponse
    {
        $action = $request->request->get('action'); // 'activate' or 'deactivate'
        
        if (!in_array($action, ['activate', 'deactivate'])) {
            return new JsonResponse(['success' => false, 'message' => 'Action invalide'], 400);
        }

        try {
            // Vérifier les dépendances avant activation
            if ($action === 'activate') {
                $errors = $moduleManager->canActivateModule($moduleName);
                if (!empty($errors)) {
                    return new JsonResponse([
                        'success' => false, 
                        'message' => 'Dépendances manquantes : ' . implode(' ', $errors)
                    ], 400);
                }
            }

            $success = $action === 'activate' 
                ? $moduleManager->activateModule($moduleName)
                : $moduleManager->deactivateModule($moduleName);

            if ($success) {
                $module = $moduleManager->getModule($moduleName);
                $displayName = $module->getDisplayName();
                return new JsonResponse([
                    'success' => true,
                    'message' => sprintf('Module "%s" %s avec succès', $displayName, $action === 'activate' ? 'activé' : 'désactivé'),
                    'active' => $module->isActive()
                ]);
            }

            return new JsonResponse(['success' => false, 'message' => 'Module non trouvé'], 404);

        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur inattendue : ' . $e->getMessage()], 500);
        }
    }
}