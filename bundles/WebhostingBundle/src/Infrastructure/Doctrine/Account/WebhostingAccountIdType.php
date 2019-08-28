<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Infrastructure\Doctrine\Account;

use ParkManager\Bundle\CoreBundle\Doctrine\DomainIdType;
use ParkManager\Bundle\WebhostingBundle\Domain\Account\WebhostingAccountId;

final class WebhostingAccountIdType extends DomainIdType
{
    public const NAME         = 'park_manager_webhosting_account_id';
    public const OBJECT_CLASS = WebhostingAccountId::class;
}
