<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/test-roles')]
class TestRoleController extends AbstractController
{
    #[Route('/', name: 'test_roles')]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupérer tous les rôles
        $roles = $em->getRepository(Role::class)->findAll();
        
        // Récupérer tous les utilisateurs avec leurs rôles
        $users = $em->getRepository(User::class)->findAll();
        
        return $this->render('test/roles.html.twig', [
            'roles' => $roles,
            'users' => $users,
            'current_user' => $this->getUser()
        ]);
    }
    
    #[Route('/assign/{userId}/{roleId}', name: 'test_assign_role')]
    public function assignRole(
        int $userId, 
        int $roleId, 
        EntityManagerInterface $em
    ): Response {
        $user = $em->getRepository(User::class)->find($userId);
        $role = $em->getRepository(Role::class)->find($roleId);
        
        if (!$user || !$role) {
            throw $this->createNotFoundException('User or Role not found');
        }
        
        // Vérifier si l'attribution existe déjà
        $existingUserRole = $em->getRepository(UserRole::class)->findOneBy([
            'user' => $user,
            'role' => $role
        ]);
        
        if (!$existingUserRole) {
            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($role);
            $userRole->setAssignedBy($this->getUser());
            
            $em->persist($userRole);
            $em->flush();
            
            $this->addFlash('success', "Rôle {$role->getDisplayName()} attribué à {$user->getFullName()}");
        } else {
            $this->addFlash('warning', "L'utilisateur a déjà ce rôle");
        }
        
        return $this->redirectToRoute('test_roles');
    }
}