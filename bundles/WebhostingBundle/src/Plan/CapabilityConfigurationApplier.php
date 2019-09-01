<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Plan;

use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Capability;

/**
 * A CapabilityConfigurationApplier applies the Capability's configuration
 * on the given webhosting account.
 *
 * This sub-system should only be used when the limitation applies
 * outside of the webhosting system (like a filesystem quota).
 */
interface CapabilityConfigurationApplier
{
    public function apply(Capability $configuration, WebhostingAccount $account): void;
}
