<?php

namespace App\Controller;

use App\Entity\DivingLevel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/diving-levels')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminDivingLevelController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_diving_levels_list')]
    public function index(): Response
    {
        $divingLevels = $this->entityManager->getRepository(DivingLevel::class)
            ->findBy([], ['sortOrder' => 'ASC', 'name' => 'ASC']);
        
        return $this->render('admin/diving_levels/index.html.twig', [
            'diving_levels' => $divingLevels,
        ]);
    }

    #[Route('/new', name: 'admin_diving_levels_new')]
    public function new(Request $request): Response
    {
        $divingLevel = new DivingLevel();
        
        if ($request->isMethod('POST')) {
            $divingLevel->setName($request->request->get('name'));
            $divingLevel->setCode($request->request->get('code'));
            $divingLevel->setDescription($request->request->get('description'));
            $divingLevel->setSortOrder((int) $request->request->get('sort_order', 0));
            $divingLevel->setActive((bool) $request->request->get('is_active', true));
            $divingLevel->setInstructor((bool) $request->request->get('is_instructor', false));

            $this->entityManager->persist($divingLevel);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Niveau de plongée créé avec succès !');
            
            return $this->redirectToRoute('admin_diving_levels_list');
        }
        
        return $this->render('admin/diving_levels/edit.html.twig', [
            'diving_level' => $divingLevel,
            'isNew' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_diving_levels_edit')]
    public function edit(DivingLevel $divingLevel, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $divingLevel->setName($request->request->get('name'));
            $divingLevel->setCode($request->request->get('code'));
            $divingLevel->setDescription($request->request->get('description'));
            $divingLevel->setSortOrder((int) $request->request->get('sort_order', 0));
            $divingLevel->setActive((bool) $request->request->get('is_active'));
            $divingLevel->setInstructor((bool) $request->request->get('is_instructor'));

            $this->entityManager->flush();
            
            $this->addFlash('success', 'Niveau de plongée mis à jour avec succès !');
            
            return $this->redirectToRoute('admin_diving_levels_list');
        }
        
        return $this->render('admin/diving_levels/edit.html.twig', [
            'diving_level' => $divingLevel,
            'isNew' => false,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_diving_levels_delete')]
    public function delete(DivingLevel $divingLevel): Response
    {
        $this->entityManager->remove($divingLevel);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Niveau de plongée supprimé avec succès !');
        
        return $this->redirectToRoute('admin_diving_levels_list');
    }

    #[Route('/{id}/toggle', name: 'admin_diving_levels_toggle')]
    public function toggle(DivingLevel $divingLevel): Response
    {
        $divingLevel->setActive(!$divingLevel->isActive());
        $this->entityManager->flush();
        
        $status = $divingLevel->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Niveau de plongée {$status} avec succès !");
        
        return $this->redirectToRoute('admin_diving_levels_list');
    }
}