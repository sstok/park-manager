<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\DomainName\Exception;

use InvalidArgumentException;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\WebhostingDomainNameId;
use function sprintf;

final class WebhostingDomainNameNotFound extends InvalidArgumentException
{
    public static function withId(WebhostingDomainNameId $id): self
    {
        return new self(sprintf('Webhosting domain-name with id "%s" does not exist.', $id->toString()));
    }
}
