<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Application\Service\Package;

use ParkManager\Component\ApplicationFoundation\Message\ServiceMessages;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Package\Capability;

/**
 * A PackageConfigurationApplier applies the Capability's configuration
 * on the given webhosting account.
 *
 * This sub-system should only be used when the limitation applies
 * outside of the webhosting system (like a filesystem quota).
 */
interface PackageConfigurationApplier
{
    public function apply(Capability $configuration, WebhostingAccount $account, ServiceMessages $messages): void;
}
