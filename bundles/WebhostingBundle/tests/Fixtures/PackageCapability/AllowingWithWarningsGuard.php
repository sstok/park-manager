<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PackageCapability;

use ParkManager\Component\ApplicationFoundation\Message\ServiceMessage;
use ParkManager\Component\ApplicationFoundation\Message\ServiceMessages;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Package\Capability;
use ParkManager\Bundle\WebhostingBundle\Package\CapabilityGuard;

final class AllowingWithWarningsGuard implements CapabilityGuard
{
    public function decide(Capability $configuration, array $context, WebhostingAccount $account): bool
    {
        return true;
    }
}
