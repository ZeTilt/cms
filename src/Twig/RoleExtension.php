<?php

namespace App\Twig;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RoleExtension extends AbstractExtension
{
    public function __construct(
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_role_hierarchy', [$this, 'hasRoleHierarchy']),
            new TwigFunction('has_minimum_hierarchy_level', [$this, 'hasMinimumHierarchyLevel']),
            new TwigFunction('get_user_roles_display', [$this, 'getUserRolesDisplay']),
        ];
    }

    /**
     * Vérifie si l'utilisateur connecté a un rôle spécifique (système classique ou hiérarchique)
     */
    public function hasRoleHierarchy(string $roleName): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        // Vérifier d'abord les rôles classiques Symfony
        if ($this->security->isGranted($roleName)) {
            return true;
        }

        // Vérifier ensuite les rôles hiérarchiques
        try {
            return $user->hasUserRole($roleName);
        } catch (\Exception $e) {
            // En cas d'erreur, on retourne false et log l'erreur
            error_log("RoleExtension: Erreur lors de la vérification du rôle $roleName: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si l'utilisateur a au moins le niveau hiérarchique requis
     */
    public function hasMinimumHierarchyLevel(int $requiredLevel): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        return $user->hasMinimumHierarchyLevel($requiredLevel);
    }

    /**
     * Obtient l'affichage des rôles de l'utilisateur connecté
     */
    public function getUserRolesDisplay(): array
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return [];
        }

        return $user->getDisplayRoles();
    }
}