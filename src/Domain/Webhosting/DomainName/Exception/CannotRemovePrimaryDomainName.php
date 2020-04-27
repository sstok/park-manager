<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\DomainName\Exception;

use InvalidArgumentException;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainNameId;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class CannotRemovePrimaryDomainName extends InvalidArgumentException
{
    public static function of(WebhostingDomainNameId $domainName, SpaceId $spaceId): self
    {
        return new self(
            \sprintf(
                'Webhosting domain-name "%s" of space %s is marked as primary and cannot be removed.',
                $domainName->toString(),
                $spaceId->toString()
            )
        );
    }
}
