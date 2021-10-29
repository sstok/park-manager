<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space\Exception;

use InvalidArgumentException;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class CannotTransferSystemWebhostingSpace extends InvalidArgumentException
{
    public static function withId(SpaceId $id): self
    {
        return new self(
            sprintf(
                'Webhosting space %s is owned by the system and cannot be transferred.',
                $id->toString()
            )
        );
    }
}
