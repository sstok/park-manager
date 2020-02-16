<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface PermissionDecider
{
    public const DECIDE_ABSTAIN = 0;
    public const DECIDE_DENY = -1;
    public const DECIDE_ALLOW = 1;

    /**
     * @return int One of the interface constants DECIDE_ABSTAIN, DECIDE_DENY or DECIDE_ALLOW
     */
    public function decide(Permission $permission, TokenInterface $token, SecurityUser $user, PermissionAccessManager $permissionAccess): int;
}
