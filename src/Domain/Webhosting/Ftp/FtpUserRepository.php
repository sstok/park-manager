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
use ParkManager\Domain\Webhosting\Ftp\Exception\FtpUserNotFound;
use ParkManager\Domain\Webhosting\Space\SpaceId;

#[Repository]
interface FtpUserRepository
{
    /**
     * @throws FtpUserNotFound
     */
    public function get(FtpUserId $id): FtpUser;

    /**
     * @return ResultSet<FtpUser>
     */
    public function all(SpaceId $space): ResultSet;

    public function save(FtpUser $user): void;

    public function remove(FtpUser $user): void;
}
