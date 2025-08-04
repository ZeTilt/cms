<?php

namespace App\Controller\Admin;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
use App\Form\RoleType;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/roles')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class RoleController extends AbstractController
{
    #[Route('/', name: 'admin_roles_list')]
    public function list(EntityManagerInterface $em, PermissionService $permissionService): Response
    {
        // Synchroniser les permissions automatiquement
        $syncedCount = $permissionService->syncPermissions();
        if ($syncedCount > 0) {
            $this->addFlash('info', "{$syncedCount} nouvelles permissions ont été synchronisées.");
        }
        
        $roles = $em->getRepository(Role::class)->findBy([], ['hierarchy' => 'DESC']);
        
        // Compter les utilisateurs par rôle
        $roleStats = [];
        foreach ($roles as $role) {
            $activeUserRoles = $em->getRepository(UserRole::class)->findBy(['role' => $role, 'active' => true]);
            $totalUserRoles = $em->getRepository(UserRole::class)->findBy(['role' => $role]);
            
            $roleStats[$role->getId()] = [
                'activeUsers' => count($activeUserRoles),
                'totalAssignments' => count($totalUserRoles)
            ];
        }
        
        return $this->render('admin/roles/list.html.twig', [
            'roles' => $roles,
            'roleStats' => $roleStats
        ]);
    }

    #[Route('/{id}', name: 'admin_roles_show', requirements: ['id' => '\d+'])]
    public function show(Role $role, EntityManagerInterface $em): Response
    {
        // Récupérer toutes les attributions de ce rôle
        $userRoles = $em->getRepository(UserRole::class)->findBy(
            ['role' => $role], 
            ['assignedAt' => 'DESC']
        );
        
        return $this->render('admin/roles/show.html.twig', [
            'role' => $role,
            'userRoles' => $userRoles
        ]);
    }

    #[Route('/{id}/users', name: 'admin_roles_manage_users', requirements: ['id' => '\d+'])]
    public function manageUsers(Role $role, Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer tous les utilisateurs
        $allUsers = $em->getRepository(User::class)->findBy(['active' => true], ['lastName' => 'ASC', 'firstName' => 'ASC']);
        
        // Récupérer les utilisateurs qui ont déjà ce rôle
        $currentUserRoles = $em->getRepository(UserRole::class)->findBy(['role' => $role, 'active' => true]);
        $currentUserIds = array_map(fn($ur) => $ur->getUser()->getId(), $currentUserRoles);
        
        // Traitement du formulaire d'attribution
        if ($request->isMethod('POST')) {
            $selectedUserIds = $request->request->all('selected_users') ?? [];
            
            // Désactiver tous les rôles actuels pour ce rôle
            foreach ($currentUserRoles as $userRole) {
                $userRole->setActive(false);
            }
            
            // Créer de nouvelles attributions pour les utilisateurs sélectionnés
            foreach ($selectedUserIds as $userId) {
                $user = $em->getRepository(User::class)->find($userId);
                if ($user) {
                    // Vérifier s'il existe déjà une attribution inactive
                    $existingUserRole = $em->getRepository(UserRole::class)->findOneBy([
                        'user' => $user,
                        'role' => $role
                    ]);
                    
                    if ($existingUserRole) {
                        $existingUserRole->setActive(true);
                        $existingUserRole->setAssignedBy($this->getUser());
                        $existingUserRole->setAssignedAt(new \DateTimeImmutable());
                    } else {
                        $userRole = new UserRole();
                        $userRole->setUser($user);
                        $userRole->setRole($role);
                        $userRole->setAssignedBy($this->getUser());
                        $em->persist($userRole);
                    }
                }
            }
            
            $em->flush();
            
            $this->addFlash('success', 'Les attributions de rôles ont été mises à jour.');
            return $this->redirectToRoute('admin_roles_show', ['id' => $role->getId()]);
        }
        
        return $this->render('admin/roles/manage_users.html.twig', [
            'role' => $role,
            'allUsers' => $allUsers,
            'currentUserIds' => $currentUserIds
        ]);
    }

    #[Route('/user-role/{id}/toggle', name: 'admin_roles_toggle_user_role', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleUserRole(UserRole $userRole, EntityManagerInterface $em): Response
    {
        $userRole->setActive(!$userRole->isActive());
        $em->flush();
        
        $status = $userRole->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Le rôle {$userRole->getRole()->getDisplayName()} a été {$status} pour {$userRole->getUser()->getFullName()}.");
        
        return $this->redirectToRoute('admin_roles_show', ['id' => $userRole->getRole()->getId()]);
    }

    #[Route('/create', name: 'admin_roles_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleType::class, $role);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du nom
            $existingRole = $em->getRepository(Role::class)->findOneBy(['name' => $role->getName()]);
            if ($existingRole) {
                $this->addFlash('error', 'Un rôle avec ce nom technique existe déjà.');
                return $this->render('admin/roles/create.html.twig', [
                    'form' => $form,
                    'role' => $role
                ]);
            }

            $em->persist($role);
            $em->flush();
            
            $this->addFlash('success', "Le rôle {$role->getDisplayName()} a été créé avec succès.");
            return $this->redirectToRoute('admin_roles_show', ['id' => $role->getId()]);
        }
        
        return $this->render('admin/roles/create.html.twig', [
            'form' => $form,
            'role' => $role
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_roles_edit', requirements: ['id' => '\d+'])]
    public function edit(Role $role, Request $request, EntityManagerInterface $em): Response
    {
        // Protéger les rôles système critiques
        if (in_array($role->getName(), ['ROLE_SUPER_ADMIN', 'ROLE_USER'])) {
            $this->addFlash('warning', 'Les rôles système ne peuvent pas être modifiés.');
            return $this->redirectToRoute('admin_roles_show', ['id' => $role->getId()]);
        }

        $form = $this->createForm(RoleType::class, $role);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du nom (sauf pour le rôle actuel)
            $existingRole = $em->getRepository(Role::class)->findOneBy(['name' => $role->getName()]);
            if ($existingRole && $existingRole->getId() !== $role->getId()) {
                $this->addFlash('error', 'Un autre rôle avec ce nom technique existe déjà.');
                return $this->render('admin/roles/edit.html.twig', [
                    'form' => $form,
                    'role' => $role
                ]);
            }

            $role->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            
            $this->addFlash('success', "Le rôle {$role->getDisplayName()} a été modifié avec succès.");
            return $this->redirectToRoute('admin_roles_show', ['id' => $role->getId()]);
        }
        
        return $this->render('admin/roles/edit.html.twig', [
            'form' => $form,
            'role' => $role
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_roles_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Role $role, Request $request, EntityManagerInterface $em): Response
    {
        // Protéger les rôles système critiques
        $protectedRoles = ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER'];
        if (in_array($role->getName(), $protectedRoles)) {
            $this->addFlash('error', 'Les rôles système ne peuvent pas être supprimés.');
            return $this->redirectToRoute('admin_roles_show', ['id' => $role->getId()]);
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete_role_' . $role->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_roles_show', ['id' => $role->getId()]);
        }

        // Vérifier si des utilisateurs ont ce rôle
        $activeUserRoles = $em->getRepository(UserRole::class)->findBy(['role' => $role, 'active' => true]);
        if (count($activeUserRoles) > 0) {
            $this->addFlash('error', 'Impossible de supprimer un rôle attribué à des utilisateurs. Désattribuez d\'abord ce rôle.');
            return $this->redirectToRoute('admin_roles_show', ['id' => $role->getId()]);
        }

        $roleName = $role->getDisplayName();
        
        // Supprimer toutes les attributions inactives
        $allUserRoles = $em->getRepository(UserRole::class)->findBy(['role' => $role]);
        foreach ($allUserRoles as $userRole) {
            $em->remove($userRole);
        }

        $em->remove($role);
        $em->flush();
        
        $this->addFlash('success', "Le rôle {$roleName} a été supprimé avec succès.");
        return $this->redirectToRoute('admin_roles_list');
    }

    #[Route('/permissions/sync', name: 'admin_roles_sync_permissions', methods: ['POST'])]
    public function syncPermissions(PermissionService $permissionService): Response
    {
        $syncedCount = $permissionService->syncPermissions();
        
        if ($syncedCount > 0) {
            $this->addFlash('success', "{$syncedCount} nouvelles permissions ont été synchronisées.");
        } else {
            $this->addFlash('info', 'Toutes les permissions sont déjà à jour.');
        }
        
        return $this->redirectToRoute('admin_roles_list');
    }
}