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

namespace ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\Capability;

use ParkManager\Component\ApplicationFoundation\Message\ServiceMessages;
use ParkManager\Module\WebhostingModule\Application\Service\Package\PackageConfigurationApplier;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Package\Capability;

final class MonthlyTrafficQuotaApplier implements PackageConfigurationApplier
{
    public function apply(Capability $configuration, WebhostingAccount $account, ServiceMessages $messages): void
    {
    }
}
