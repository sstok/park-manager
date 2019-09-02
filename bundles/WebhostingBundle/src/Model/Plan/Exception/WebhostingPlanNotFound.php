<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\Plan\Exception;

use InvalidArgumentException;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;
use function sprintf;

final class WebhostingPlanNotFound extends InvalidArgumentException
{
    public static function withId(WebhostingPlanId $id): self
    {
        return new self(sprintf('Webhosting plan with id "%s" does not exist.', $id->toString()));
    }
}
