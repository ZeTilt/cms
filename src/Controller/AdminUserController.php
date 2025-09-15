<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_SUPER_ADMIN')]
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
            $user->setFirstName($request->request->get('firstName') ?: '');
            $user->setLastName($request->request->get('lastName') ?: '');
            
            $roles = $request->request->all('roles') ?: [];
            $user->setRoles($roles);
            
            $plainPassword = $request->request->get('password');
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur créé avec succès !');
            
            return $this->redirectToRoute('admin_users_list');
        }
        
        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'isNew' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_users_edit')]
    public function edit(User $user, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            $user->setFirstName($request->request->get('firstName') ?: '');
            $user->setLastName($request->request->get('lastName') ?: '');
            
            $roles = $request->request->all('roles') ?: [];
            $user->setRoles($roles);
            
            $plainPassword = $request->request->get('password');
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur mis à jour avec succès !');
            
            return $this->redirectToRoute('admin_users_list');
        }
        
        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'isNew' => false,
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
}