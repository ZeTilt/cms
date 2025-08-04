<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users/approval')]
#[IsGranted('ROLE_ADMIN')]
class UserApprovalController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_users_approval_list')]
    public function list(): Response
    {
        $pendingUsers = $this->entityManager->getRepository(User::class)
            ->findBy(['status' => 'pending_approval'], ['createdAt' => 'DESC']);

        return $this->render('admin/users/approval_list.html.twig', [
            'pending_users' => $pendingUsers,
        ]);
    }

    #[Route('/{id}/approve', name: 'admin_users_approve', methods: ['POST'])]
    public function approve(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('approve_user_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_users_approval_list');
        }

        $user->approve();
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('L\'utilisateur %s a été approuvé.', $user->getFullName()));
        
        // TODO: Envoyer un email de confirmation à l'utilisateur
        
        return $this->redirectToRoute('admin_users_approval_list');
    }

    #[Route('/{id}/reject', name: 'admin_users_reject', methods: ['POST'])]
    public function reject(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('reject_user_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_users_approval_list');
        }

        $reason = $request->request->get('reason', '');
        
        $user->reject();
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('L\'utilisateur %s a été rejeté.', $user->getFullName()));
        
        // TODO: Envoyer un email de rejet à l'utilisateur avec la raison
        
        return $this->redirectToRoute('admin_users_approval_list');
    }

    #[Route('/stats', name: 'admin_users_approval_stats')]
    public function stats(): Response
    {
        $stats = [
            'pending' => $this->entityManager->getRepository(User::class)->count(['status' => 'pending_approval']),
            'approved' => $this->entityManager->getRepository(User::class)->count(['status' => 'approved']),
            'rejected' => $this->entityManager->getRepository(User::class)->count(['status' => 'rejected']),
        ];

        return $this->json($stats);
    }
}