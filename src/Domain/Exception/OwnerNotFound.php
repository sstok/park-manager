<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Exception;

use ParkManager\Domain\OwnerId;

final class OwnerNotFound extends NotFoundException
{
    public static function withId(OwnerId $id): self
    {
        return new self(\sprintf('Owner with ID %s does not exist. The actual owner must be registered first.', $id->toString()), ['id' => $id->toString()]);
    }
}
