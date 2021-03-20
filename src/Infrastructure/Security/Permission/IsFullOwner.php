<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security\Permission;

use ParkManager\Domain\Owner;
use ParkManager\Infrastructure\Security\Permission;

final class IsFullOwner implements Permission
{
    public Owner $owner;

    public function __construct(Owner $owner)
    {
        $this->owner = $owner;
    }
}
