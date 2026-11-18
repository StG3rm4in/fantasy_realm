<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
    }

    /**
     * Appelé APRÈS la vérification du mot de passe : sinon, un mail et un mot de
     * passe quelconque suffisaient pour savoir qu'un compte existe et est suspendu.
     */
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isSuspendu()) {
            throw new CustomUserMessageAccountStatusException('Votre compte est suspendu. Veuillez contacter un administrateur.');
        }
    }
}
