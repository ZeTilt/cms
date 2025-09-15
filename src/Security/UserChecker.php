<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérifier si l'email est vérifié
        if (!$user->isEmailVerified()) {
            throw new CustomUserMessageAccountStatusException('Vous devez vérifier votre adresse email avant de vous connecter. Vérifiez votre boîte mail.');
        }

        // Vérifier si le compte est approuvé
        if ($user->getStatus() === 'rejected') {
            throw new CustomUserMessageAccountStatusException('Votre compte a été rejeté par un administrateur.');
        }

        if ($user->getStatus() === 'pending') {
            throw new CustomUserMessageAccountStatusException('Votre compte est en attente de validation par un administrateur.');
        }

        // Vérifier si le compte est actif
        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Votre compte est désactivé.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Vérifications post-authentification si nécessaire
    }
}