<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Domain\Account\Exception;

use InvalidArgumentException;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use function sprintf;

final class WebhostingAccountNotFound extends InvalidArgumentException
{
    public static function withId(WebhostingAccountId $id): self
    {
        return new self(sprintf('Webhosting account with id "%s" does not exist.', $id->toString()));
    }
}
