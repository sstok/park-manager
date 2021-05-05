<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security\Voter;

use ParkManager\Infrastructure\Security\SecurityUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class SwitchUserVoter implements VoterInterface
{
    public const CAN_SWITCH_USER = 'CAN_SWITCH_USER';

    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        $user = $token->getUser();

        if (! $user instanceof SecurityUser || ! $subject instanceof SecurityUser) {
            return self::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if ($attribute === self::CAN_SWITCH_USER) {
                return $this->canSwitchUser($subject, $user) ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    private function canSwitchUser(SecurityUser $subject, SecurityUser $user): bool
    {
        // Do not allow impersonating other Admins.
        if ($subject->isAdmin()) {
            return false;
        }

        if (! $subject->isEnabled()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }
}
