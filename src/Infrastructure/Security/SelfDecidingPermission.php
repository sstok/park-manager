<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/** Marker interface for application permissions that are self invoking */
interface SelfDecidingPermission extends Permission
{
    /**
     * @return int One of the constants PermissionDecider::DECIDE_ABSTAIN, PermissionDecider::DECIDE_DENY or PermissionDecider::DECIDE_ALLOW
     */
    public function __invoke(TokenInterface $token, SecurityUser $user, PermissionAccessManager $permissionAccess): int;
}
