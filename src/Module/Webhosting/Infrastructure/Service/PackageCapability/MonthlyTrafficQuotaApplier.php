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

namespace ParkManager\Module\Webhosting\Infrastructure\Service\PackageCapability;

use ParkManager\Component\Model\LogMessage\LogMessages;
use ParkManager\Module\Webhosting\Domain\Account\WebhostingAccount;
use ParkManager\Module\Webhosting\Domain\Package\Capability;
use ParkManager\Module\Webhosting\Domain\Package\ConfigurationApplier;

final class MonthlyTrafficQuotaApplier implements ConfigurationApplier
{
    public function apply(Capability $configuration, WebhostingAccount $account, LogMessages $messages): void
    {
    }
}
