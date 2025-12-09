<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dp/caci')]
#[IsGranted('ROLE_DP')]
class CaciController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'dp_caci_list')]
    public function index(): Response
    {
        // Récupérer les utilisateurs avec CACI en attente de vérification
        $pendingUsers = $this->userRepository->findCaciPendingVerification();

        // Récupérer les utilisateurs avec CACI expiré
        $expiredUsers = $this->userRepository->findCaciExpired();

        // Récupérer les utilisateurs sans CACI déclaré
        $missingUsers = $this->userRepository->findCaciMissing();

        // Statistiques
        $stats = [
            'pending' => count($pendingUsers),
            'expired' => count($expiredUsers),
            'missing' => count($missingUsers),
            'valid' => $this->userRepository->countCaciValid(),
        ];

        return $this->render('dp/caci/index.html.twig', [
            'pendingUsers' => $pendingUsers,
            'expiredUsers' => $expiredUsers,
            'missingUsers' => $missingUsers,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/verify', name: 'dp_caci_verify', methods: ['POST'])]
    public function verify(User $user, Request $request): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('caci_verify_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('dp_caci_list');
        }

        // Vérifier que l'utilisateur a une date de CACI déclarée
        if (!$user->getMedicalCertificateExpiry()) {
            $this->addFlash('error', 'Cet utilisateur n\'a pas déclaré de date de CACI.');
            return $this->redirectToRoute('dp_caci_list');
        }

        // Vérifier que le CACI n'est pas expiré
        if ($user->getMedicalCertificateExpiry() < new \DateTime('today')) {
            $this->addFlash('error', 'Impossible de vérifier un CACI expiré.');
            return $this->redirectToRoute('dp_caci_list');
        }

        // Valider le CACI
        $user->verifyCaci($this->getUser());
        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'CACI de %s vérifié avec succès (valide jusqu\'au %s).',
            $user->getFullName(),
            $user->getMedicalCertificateExpiry()->format('d/m/Y')
        ));

        return $this->redirectToRoute('dp_caci_list');
    }

    #[Route('/verify-batch', name: 'dp_caci_verify_batch', methods: ['POST'])]
    public function verifyBatch(Request $request): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('caci_verify_batch', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('dp_caci_list');
        }

        $userIds = $request->request->all('user_ids');

        if (empty($userIds)) {
            $this->addFlash('warning', 'Aucun utilisateur sélectionné.');
            return $this->redirectToRoute('dp_caci_list');
        }

        $dp = $this->getUser();
        $verified = 0;
        $skipped = 0;

        foreach ($userIds as $userId) {
            $user = $this->userRepository->find($userId);

            if (!$user) {
                continue;
            }

            // Vérifier que le CACI n'est pas expiré et qu'il y a une date déclarée
            if (!$user->getMedicalCertificateExpiry() ||
                $user->getMedicalCertificateExpiry() < new \DateTime('today')) {
                $skipped++;
                continue;
            }

            // Vérifier le CACI
            $user->verifyCaci($dp);
            $verified++;
        }

        $this->entityManager->flush();

        if ($verified > 0) {
            $this->addFlash('success', sprintf('%d CACI vérifié(s) avec succès.', $verified));
        }

        if ($skipped > 0) {
            $this->addFlash('warning', sprintf('%d CACI ignoré(s) (expirés ou sans date).', $skipped));
        }

        return $this->redirectToRoute('dp_caci_list');
    }

    #[Route('/{id}/reset', name: 'dp_caci_reset', methods: ['POST'])]
    public function reset(User $user, Request $request): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('caci_reset_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('dp_caci_list');
        }

        $user->resetCaciVerification();
        $this->entityManager->flush();

        $this->addFlash('info', sprintf(
            'Vérification du CACI de %s réinitialisée.',
            $user->getFullName()
        ));

        return $this->redirectToRoute('dp_caci_list');
    }
}
