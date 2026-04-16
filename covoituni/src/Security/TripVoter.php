<?php

namespace App\Security;

use App\Entity\Trip;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TripVoter extends Voter
{
    public const EDIT = 'EDIT';

    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Trip
            && \in_array($attribute, [self::EDIT, self::DELETE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Trip $subject */
        $driver = $subject->getDriver();
        if (null === $driver || null === $driver->getId() || null === $user->getId()) {
            return false;
        }

        return $driver->getId() === $user->getId();
    }
}
