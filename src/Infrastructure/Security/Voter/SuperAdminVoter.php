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
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;

final class SuperAdminVoter implements CacheableVoterInterface
{
    /**
     * @param array<int, mixed> $attributes
     *
     * @phpstan-return self::ACCESS_GRANTED|self::ACCESS_ABSTAIN|self::ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        $user = $token->getUser();

        if (! $user instanceof SecurityUser || ! $user->isEnabled()) {
            return self::ACCESS_ABSTAIN;
        }

        if ($user->isSuperAdmin()) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_ABSTAIN;
    }

    public function supportsAttribute(string $attribute): bool
    {
        return true;
    }

    public function supportsType(string $subjectType): bool
    {
        return true;
    }
}
