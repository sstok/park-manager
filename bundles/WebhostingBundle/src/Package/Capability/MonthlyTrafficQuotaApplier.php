<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Package\Capability;

use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Package\Capability;
use ParkManager\Bundle\WebhostingBundle\Package\PackageConfigurationApplier;

final class MonthlyTrafficQuotaApplier implements PackageConfigurationApplier
{
    public function apply(Capability $configuration, WebhostingAccount $account): void
    {
    }
}
