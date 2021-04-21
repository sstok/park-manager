<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\SuspensionLevel;

final class MarkSpaceAccessAsSuspended
{
    public SpaceId $id;
    public SuspensionLevel $level;

    public function __construct(SpaceId $id, SuspensionLevel $level)
    {
        $this->id = $id;
        $this->level = $level;
    }
}
