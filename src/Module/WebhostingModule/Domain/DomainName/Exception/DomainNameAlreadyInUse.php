<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Domain\DomainName\Exception;

use InvalidArgumentException;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\DomainName;
use function sprintf;

final class DomainNameAlreadyInUse extends InvalidArgumentException
{
    public static function byAccountId(DomainName $domainName, WebhostingAccountId $accountId): self
    {
        return new self(
            sprintf(
                'Webhosting domain name "%s" is already in use by account %s.',
                $domainName->toString(),
                $accountId->toString()
            )
        );
    }
}
