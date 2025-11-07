<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\DivingLevel;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', name: 'admin_users_list')]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'admin_users_new')]
    public function new(Request $request): Response
    {
        $user = new User();
        
        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            $user->setFirstName($request->request->get('firstName') ?: null);
            $user->setLastName($request->request->get('lastName') ?: null);
            
            $roles = $request->request->all('roles') ?: [];
            $user->setRoles($roles);
            
            $plainPassword = $request->request->get('password');
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // Sauvegarder le niveau de plongée
            if ($request->request->get('diving_level')) {
                $divingLevelId = $request->request->get('diving_level');
                $divingLevel = $this->entityManager->getRepository(DivingLevel::class)->find($divingLevelId);
                if ($divingLevel) {
                    $user->setHighestDivingLevel($divingLevel);
                }
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur créé avec succès !');
            
            return $this->redirectToRoute('admin_users_list');
        }
        
        // Récupérer les niveaux de plongée actifs
        $divingLevels = $this->entityManager->getRepository(DivingLevel::class)
            ->findBy(['isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC']);

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'isNew' => true,
            'diving_levels' => $divingLevels,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_users_edit')]
    public function edit(User $user, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            $user->setFirstName($request->request->get('firstName') ?: null);
            $user->setLastName($request->request->get('lastName') ?: null);
            
            $roles = $request->request->all('roles') ?: [];
            $user->setRoles($roles);
            
            $plainPassword = $request->request->get('password');
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // Sauvegarder le niveau de plongée
            $divingLevelId = $request->request->get('diving_level');
            if ($divingLevelId) {
                $divingLevel = $this->entityManager->getRepository(DivingLevel::class)->find($divingLevelId);
                $user->setHighestDivingLevel($divingLevel);
            } else {
                $user->setHighestDivingLevel(null);
            }

            $this->entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur mis à jour avec succès !');
            
            return $this->redirectToRoute('admin_users_list');
        }

        // Récupérer les niveaux de plongée actifs
        $divingLevels = $this->entityManager->getRepository(DivingLevel::class)
            ->findBy(['isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC']);

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'isNew' => false,
            'diving_levels' => $divingLevels,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_users_delete')]
    public function delete(User $user): Response
    {
        // Empêcher la suppression de son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_users_list');
        }
        
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        
        return $this->redirectToRoute('admin_users_list');
    }

    #[Route('/{id}/approve', name: 'admin_users_approve')]
    public function approve(User $user): Response
    {
        if ($user->getStatus() !== 'pending') {
            $this->addFlash('error', 'Seuls les comptes en attente peuvent être validés.');
            return $this->redirectToRoute('admin_users_list');
        }

        $user->setStatus('approved');
        $user->setActive(true);
        $this->entityManager->flush();

        $this->addFlash('success', "Inscription de {$user->getFullName()} validée avec succès !");
        
        return $this->redirectToRoute('admin_users_list');
    }

    #[Route('/{id}/reject', name: 'admin_users_reject')]
    public function reject(User $user): Response
    {
        if ($user->getStatus() !== 'pending') {
            $this->addFlash('error', 'Seuls les comptes en attente peuvent être rejetés.');
            return $this->redirectToRoute('admin_users_list');
        }

        $user->setStatus('rejected');
        $user->setActive(false);
        $this->entityManager->flush();

        $this->addFlash('warning', "Inscription de {$user->getFullName()} rejetée.");
        
        return $this->redirectToRoute('admin_users_list');
    }
}