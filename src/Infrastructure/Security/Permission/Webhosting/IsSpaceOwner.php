<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security\Permission\Webhosting;

use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Infrastructure\Security\PermissionAccessManager;
use ParkManager\Infrastructure\Security\PermissionDecider;
use ParkManager\Infrastructure\Security\SecurityUser;
use ParkManager\Infrastructure\Security\SelfDecidingPermission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class IsSpaceOwner implements SelfDecidingPermission
{
    private Space $space;

    public function __construct(Space $space)
    {
        $this->space = $space;
    }

    public function __invoke(TokenInterface $token, SecurityUser $user, PermissionAccessManager $permissionAccess): int
    {
        $owner = $this->space->owner;

        if ($user->isAdmin()) {
            return PermissionDecider::DECIDE_ALLOW;
        }

        // Given the User is not an Admin and the owner is null access is explicitly denied.
        // Only Admin can access private spaces. Even sub-resources should not be accessible.
        if ($owner === null) {
            return PermissionDecider::DECIDE_DENY;
        }

        // If the current user is not the owner abstain access in-case another permission
        // can be more explicit. If all abstain access is denied anyway.
        return $owner->id->toString() === $user->getId() ? PermissionDecider::DECIDE_ALLOW : PermissionDecider::DECIDE_ABSTAIN;
    }
}
