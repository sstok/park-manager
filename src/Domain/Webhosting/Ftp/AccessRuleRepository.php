<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Ftp;

use Lifthill\Component\Common\Domain\Attribute\Repository;
use Lifthill\Component\Common\Domain\ResultSet;
use ParkManager\Domain\Webhosting\Ftp\Exception\AccessRuleNotFound;
use ParkManager\Domain\Webhosting\Space\SpaceId;

#[Repository]
interface AccessRuleRepository
{
    /**
     * @throws AccessRuleNotFound
     */
    public function get(AccessRuleId $id): AccessRule;

    /**
     * Returns whether there is at least one (enabled) "allow" rule for the space or user.
     *
     * Note: Only for the given type, if there is no rule for the user but one does
     * exist for the space of the FTPUser, this will still return false.
     *
     * When there is at least one enabled (per level) explicit allow-rule
     * all blocking rules are ignored.
     */
    public function hasAnyAllow(FtpUserId | SpaceId $id): bool;

    /**
     * @return ResultSet<AccessRule>
     */
    public function allOfSpace(SpaceId $space): ResultSet;

    /**
     * @return ResultSet<AccessRule>
     */
    public function allOfUser(FtpUserId $user): ResultSet;

    public function save(AccessRule $rule): void;

    public function remove(AccessRule $rule): void;
}
