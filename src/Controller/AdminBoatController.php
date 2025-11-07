<?php

namespace App\Controller;

use App\Entity\Boat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/boats')]
#[IsGranted('ROLE_ADMIN')]
class AdminBoatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_boats_list')]
    public function index(): Response
    {
        $boats = $this->entityManager->getRepository(Boat::class)
            ->findBy([], ['name' => 'ASC']);

        return $this->render('admin/boats/index.html.twig', [
            'boats' => $boats,
        ]);
    }

    #[Route('/new', name: 'admin_boats_new')]
    public function new(Request $request): Response
    {
        $boat = new Boat();

        if ($request->isMethod('POST')) {
            $boat->setName($request->request->get('name'));
            $boat->setDescription($request->request->get('description'));
            $boat->setCapacity($request->request->get('capacity') ? (int) $request->request->get('capacity') : null);
            $boat->setActive((bool) $request->request->get('is_active', true));

            $this->entityManager->persist($boat);
            $this->entityManager->flush();

            $this->addFlash('success', 'Bateau créé avec succès !');

            return $this->redirectToRoute('admin_boats_list');
        }

        return $this->render('admin/boats/edit.html.twig', [
            'boat' => $boat,
            'isNew' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_boats_edit')]
    public function edit(Boat $boat, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $boat->setName($request->request->get('name'));
            $boat->setDescription($request->request->get('description'));
            $boat->setCapacity($request->request->get('capacity') ? (int) $request->request->get('capacity') : null);
            $boat->setActive((bool) $request->request->get('is_active'));

            $this->entityManager->flush();

            $this->addFlash('success', 'Bateau mis à jour avec succès !');

            return $this->redirectToRoute('admin_boats_list');
        }

        return $this->render('admin/boats/edit.html.twig', [
            'boat' => $boat,
            'isNew' => false,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_boats_delete')]
    public function delete(Boat $boat): Response
    {
        $this->entityManager->remove($boat);
        $this->entityManager->flush();

        $this->addFlash('success', 'Bateau supprimé avec succès !');

        return $this->redirectToRoute('admin_boats_list');
    }

    #[Route('/{id}/toggle', name: 'admin_boats_toggle')]
    public function toggle(Boat $boat): Response
    {
        $boat->setActive(!$boat->isActive());
        $this->entityManager->flush();

        $status = $boat->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Bateau {$status} avec succès !");

        return $this->redirectToRoute('admin_boats_list');
    }
}
