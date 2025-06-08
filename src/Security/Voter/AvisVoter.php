<?php

namespace App\Security\Voter;

use App\Entity\Avis;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AvisVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, ['EDIT', 'DELETE']) && $subject instanceof Avis;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Avis $avis */
        $avis = $subject;

        // Admin peut tout faire
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Seul l'auteur de l'avis peut modifier ou supprimer
        return $avis->getUtilisateur() === $user;
    }
}
