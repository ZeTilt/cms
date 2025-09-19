<?php

namespace App\Controller;

use App\Repository\DivingLevelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class UserProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DivingLevelRepository $divingLevelRepository
    ) {}

    #[Route('', name: 'user_profile_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        $divingLevels = $this->divingLevelRepository->findAllOrdered();

        return $this->render('user/profile/index.html.twig', [
            'user' => $user,
            'divingLevels' => $divingLevels,
        ]);
    }

    #[Route('/diving-level', name: 'user_profile_diving_level', methods: ['POST'])]
    public function updateDivingLevel(Request $request): Response
    {
        $user = $this->getUser();
        $divingLevelId = $request->request->get('diving_level_id');

        if ($divingLevelId === '') {
            // Vider le niveau de plongée
            $user->setHighestDivingLevel(null);
            $this->addFlash('success', 'Niveau de plongée supprimé de votre profil.');
        } elseif ($divingLevelId) {
            $divingLevel = $this->divingLevelRepository->find($divingLevelId);

            if (!$divingLevel || !$divingLevel->isActive()) {
                $this->addFlash('error', 'Niveau de plongée invalide.');
                return $this->redirectToRoute('user_profile_index');
            }

            $user->setHighestDivingLevel($divingLevel);
            $this->addFlash('success', 'Votre niveau de plongée a été mis à jour : ' . $divingLevel->getName());
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('user_profile_index');
    }
}