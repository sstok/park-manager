<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class TransferSpaceToOwner
{
    public function __construct(public SpaceId $space, public OwnerId $newOwner)
    {
    }

    public static function with(string $id, string $newOwner): self
    {
        return new self(SpaceId::fromString($id), OwnerId::fromString($newOwner));
    }
}
