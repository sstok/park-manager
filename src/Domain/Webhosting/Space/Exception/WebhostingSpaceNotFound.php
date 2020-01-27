<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space\Exception;

use InvalidArgumentException;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceId;

final class WebhostingSpaceNotFound extends InvalidArgumentException
{
    public static function withId(WebhostingSpaceId $id): self
    {
        return new self(\sprintf('Webhosting Space with id "%s" does not exist.', $id->toString()));
    }
}
