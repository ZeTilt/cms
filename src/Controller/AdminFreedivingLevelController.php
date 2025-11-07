<?php

namespace App\Controller;

use App\Entity\FreedivingLevel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/freediving-levels')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminFreedivingLevelController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_freediving_levels_list')]
    public function index(): Response
    {
        $divingLevels = $this->entityManager->getRepository(FreedivingLevel::class)
            ->findBy([], ['sortOrder' => 'ASC', 'name' => 'ASC']);
        
        return $this->render('admin/freediving_levels/index.html.twig', [
            'freediving_levels' => $divingLevels,
        ]);
    }

    #[Route('/new', name: 'admin_freediving_levels_new')]
    public function new(Request $request): Response
    {
        $divingLevel = new FreedivingLevel();
        
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
            
            return $this->redirectToRoute('admin_freediving_levels_list');
        }
        
        return $this->render('admin/freediving_levels/edit.html.twig', [
            'freediving_level' => $divingLevel,
            'isNew' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_freediving_levels_edit')]
    public function edit(FreedivingLevel $divingLevel, Request $request): Response
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
            
            return $this->redirectToRoute('admin_freediving_levels_list');
        }
        
        return $this->render('admin/freediving_levels/edit.html.twig', [
            'freediving_level' => $divingLevel,
            'isNew' => false,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_freediving_levels_delete')]
    public function delete(FreedivingLevel $divingLevel): Response
    {
        $this->entityManager->remove($divingLevel);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Niveau de plongée supprimé avec succès !');
        
        return $this->redirectToRoute('admin_freediving_levels_list');
    }

    #[Route('/{id}/toggle', name: 'admin_freediving_levels_toggle')]
    public function toggle(FreedivingLevel $divingLevel): Response
    {
        $divingLevel->setActive(!$divingLevel->isActive());
        $this->entityManager->flush();
        
        $status = $divingLevel->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Niveau de plongée {$status} avec succès !");
        
        return $this->redirectToRoute('admin_freediving_levels_list');
    }
}