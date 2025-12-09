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

#[Route('/dp/cotisations')]
#[IsGranted('ROLE_DP')]
class MembershipController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'dp_membership_list')]
    public function index(): Response
    {
        $currentSeason = User::getCurrentSeason();

        // Statistiques
        $allActiveUsers = $this->userRepository->findBy(['active' => true, 'status' => 'approved']);

        $paidUsers = [];
        $unpaidUsers = [];
        $expiredUsers = [];

        foreach ($allActiveUsers as $user) {
            $status = $user->getMembershipStatus();
            if ($status === 'valid') {
                $paidUsers[] = $user;
            } elseif ($status === 'expired') {
                $expiredUsers[] = $user;
            } else {
                $unpaidUsers[] = $user;
            }
        }

        // Trier les non-payés par nom
        usort($unpaidUsers, fn($a, $b) => strcmp($a->getFullName(), $b->getFullName()));

        $stats = [
            'total' => count($allActiveUsers),
            'paid' => count($paidUsers),
            'unpaid' => count($unpaidUsers),
            'expired' => count($expiredUsers),
        ];

        // Moyens de paiement disponibles
        $paymentMethods = [
            'cash' => 'Espèces',
            'check' => 'Chèque',
            'transfer' => 'Virement',
            'card' => 'Carte bancaire',
        ];

        return $this->render('dp/membership/index.html.twig', [
            'currentSeason' => $currentSeason,
            'paidUsers' => $paidUsers,
            'unpaidUsers' => $unpaidUsers,
            'expiredUsers' => $expiredUsers,
            'stats' => $stats,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    #[Route('/{id}/register', name: 'dp_membership_register', methods: ['POST'])]
    public function register(User $user, Request $request): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('membership_register_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('dp_membership_list');
        }

        $amount = $request->request->get('amount');
        $paymentMethod = $request->request->get('payment_method');
        $season = $request->request->get('season') ?: User::getCurrentSeason();

        if (!$amount || !$paymentMethod) {
            $this->addFlash('error', 'Le montant et le mode de paiement sont requis.');
            return $this->redirectToRoute('dp_membership_list');
        }

        // Enregistrer la cotisation
        $user->registerMembership(
            $this->getUser(),
            $amount,
            $paymentMethod,
            $season
        );

        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'Cotisation de %s enregistrée pour la saison %s (%s €).',
            $user->getFullName(),
            $season,
            $amount
        ));

        return $this->redirectToRoute('dp_membership_list');
    }

    #[Route('/{id}/cancel', name: 'dp_membership_cancel', methods: ['POST'])]
    public function cancel(User $user, Request $request): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('membership_cancel_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('dp_membership_list');
        }

        $user->resetMembership();
        $this->entityManager->flush();

        $this->addFlash('warning', sprintf(
            'Cotisation de %s annulée.',
            $user->getFullName()
        ));

        return $this->redirectToRoute('dp_membership_list');
    }

    #[Route('/register-batch', name: 'dp_membership_register_batch', methods: ['POST'])]
    public function registerBatch(Request $request): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('membership_register_batch', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('dp_membership_list');
        }

        $userIds = $request->request->all('user_ids');
        $amount = $request->request->get('batch_amount');
        $paymentMethod = $request->request->get('batch_payment_method');

        if (empty($userIds)) {
            $this->addFlash('warning', 'Aucun utilisateur sélectionné.');
            return $this->redirectToRoute('dp_membership_list');
        }

        if (!$amount || !$paymentMethod) {
            $this->addFlash('error', 'Le montant et le mode de paiement sont requis.');
            return $this->redirectToRoute('dp_membership_list');
        }

        $dp = $this->getUser();
        $season = User::getCurrentSeason();
        $count = 0;

        foreach ($userIds as $userId) {
            $user = $this->userRepository->find($userId);
            if ($user) {
                $user->registerMembership($dp, $amount, $paymentMethod, $season);
                $count++;
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            '%d cotisation(s) enregistrée(s) pour la saison %s.',
            $count,
            $season
        ));

        return $this->redirectToRoute('dp_membership_list');
    }
}
