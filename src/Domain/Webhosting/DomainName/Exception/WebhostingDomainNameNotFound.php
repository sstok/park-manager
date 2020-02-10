<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\DomainName\Exception;

use ParkManager\Domain\Exception\NotFoundException;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainNameId;

final class WebhostingDomainNameNotFound extends NotFoundException
{
    public static function withId(WebhostingDomainNameId $id): self
    {
        return new self(\sprintf('Webhosting domain-name with id "%s" does not exist.', $id->toString()));
    }
}
