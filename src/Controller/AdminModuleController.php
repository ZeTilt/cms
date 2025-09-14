<?php

namespace App\Controller;

use App\Repository\ModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/modules')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminModuleController extends AbstractController
{
    public function __construct(
        private ModuleRepository $moduleRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_modules')]
    public function index(): Response
    {
        $modules = $this->moduleRepository->findAll();
        
        return $this->render('admin/modules/index.html.twig', [
            'modules' => $modules,
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_modules_toggle')]
    public function toggle(int $id): Response
    {
        $module = $this->moduleRepository->find($id);
        
        if (!$module) {
            throw $this->createNotFoundException('Module non trouvé');
        }
        
        $module->setActive(!$module->isActive());
        $this->entityManager->flush();
        
        $status = $module->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Module {$module->getName()} {$status} avec succès !");
        
        return $this->redirectToRoute('admin_modules');
    }
}