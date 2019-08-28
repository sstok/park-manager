<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\Infrastructure\PackageCapability;

use ParkManager\Component\ApplicationFoundation\Message\ServiceMessages;
use ParkManager\Bundle\WebhostingBundle\Domain\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Domain\Package\Capability;
use ParkManager\Bundle\WebhostingBundle\Infrastructure\Service\Package\CapabilityGuard;

final class MonthlyTrafficQuotaGuard implements CapabilityGuard
{
    public function decide(Capability $configuration, array $context, WebhostingAccount $account, ServiceMessages $messages): bool
    {
        return true;
    }
}
