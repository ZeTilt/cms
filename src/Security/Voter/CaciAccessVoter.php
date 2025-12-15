<?php

namespace App\Security\Voter;

use App\Entity\MedicalCertificate;
use App\Entity\User;
use App\Repository\EventParticipationRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CaciAccessVoter extends Voter
{
    public const VIEW = 'CACI_VIEW';
    public const DOWNLOAD = 'CACI_DOWNLOAD';
    public const VALIDATE = 'CACI_VALIDATE';

    public function __construct(
        private EventParticipationRepository $participationRepository
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::DOWNLOAD, self::VALIDATE])
            && ($subject instanceof User || $subject instanceof MedicalCertificate);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        // Get the target user (either directly or from the certificate)
        $targetUser = $subject instanceof MedicalCertificate
            ? $subject->getUser()
            : $subject;

        // Users can always view their own CACI
        if ($currentUser->getId() === $targetUser->getId()) {
            return $attribute !== self::VALIDATE; // Can't validate own CACI
        }

        // CACI Referents can access ALL CACIs for validation
        if ($this->hasRole($currentUser, 'ROLE_CACI_REFERENT')) {
            return true;
        }

        // Admins can view all CACIs but only CACI_REFERENT can validate
        if ($this->hasRole($currentUser, 'ROLE_ADMIN')) {
            return $attribute !== self::VALIDATE;
        }

        // DPs can only VIEW CACIs of participants registered to THEIR events
        if ($this->hasRole($currentUser, 'ROLE_DP')) {
            if ($attribute === self::VALIDATE) {
                return false; // DPs cannot validate
            }

            return $this->isDpForUser($currentUser, $targetUser);
        }

        return false;
    }

    /**
     * Check if the DP is the diving director for any upcoming event
     * where the target user is registered
     */
    private function isDpForUser(User $dp, User $participant): bool
    {
        // Get upcoming events where the target user is registered
        // and the current user is the diving director
        $participations = $this->participationRepository->findActiveParticipationsForUser($participant);

        foreach ($participations as $participation) {
            $event = $participation->getEvent();

            // Check if the DP is the diving director for this event
            if ($event->getDivingDirector() && $event->getDivingDirector()->getId() === $dp->getId()) {
                // Only for upcoming events
                if ($event->getStartDate() >= new \DateTime('today')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasRole(User $user, string $role): bool
    {
        return in_array($role, $user->getRoles());
    }
}
