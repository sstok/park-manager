<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Infrastructure\Security\Permission;

use ParkManager\Infrastructure\Security\Permission;
use ParkManager\Infrastructure\Security\PermissionAccessManager;
use ParkManager\Infrastructure\Security\PermissionDecider;
use ParkManager\Infrastructure\Security\SecurityUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class IsSuperAdminDecider implements PermissionDecider
{
    public function decide(
        Permission $permission,
        TokenInterface $token,
        SecurityUser $user,
        PermissionAccessManager $permissionAccess
    ): int {
        return 0;
    }
}
