<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User;

use Lifthill\Component\Common\Domain\UniqueIdentity;
use Lifthill\Component\Common\Domain\UuidTrait;
use ParkManager\Domain\OwnerId;

final class UserId implements UniqueIdentity
{
    use UuidTrait;

    public function toOwnerId(): OwnerId
    {
        return OwnerId::fromString($this->toString());
    }
}
