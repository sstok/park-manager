<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Domain\DomainName\Exception;

use InvalidArgumentException;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\DomainName\WebhostingDomainNameId;
use function sprintf;

final class CannotTransferPrimaryDomainName extends InvalidArgumentException
{
    public static function of(WebhostingDomainNameId $domainName, WebhostingAccountId $current, WebhostingAccountId $new): self
    {
        return new self(
            sprintf(
                'Webhosting domain-name "%s" of account %s is marked as primary and cannot be transferred to account %s.',
                $domainName->toString(),
                $current->toString(),
                $new->toString()
            )
        );
    }
}
