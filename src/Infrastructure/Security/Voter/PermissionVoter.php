<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security\Voter;

use ParkManager\Infrastructure\Security\Permission;
use ParkManager\Infrastructure\Security\PermissionAccessManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class PermissionVoter implements VoterInterface
{
    private PermissionAccessManager $permissionAccessManager;

    public function __construct(PermissionAccessManager $permissionAccessManager)
    {
        $this->permissionAccessManager = $permissionAccessManager;
    }

    /**
     * @param array<int, mixed> $attributes
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        foreach ($attributes as $attribute) {
            if (! $attribute instanceof Permission) {
                continue;
            }

            return $this->permissionAccessManager->decide($attribute, $token);
        }

        return self::ACCESS_ABSTAIN;
    }
}
