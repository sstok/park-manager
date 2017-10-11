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

namespace ParkManager\Module\Webhosting\Tests\Fixtures\Infrastructure\Package\Capability;

use ParkManager\Component\Model\MessageStack\LogMessages;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccount;
use ParkManager\Module\Webhosting\Model\Package\Capability;
use ParkManager\Module\Webhosting\Model\Package\CapabilityGuard;

final class MonthlyTrafficQuotaGuard2 implements CapabilityGuard
{
    public function can(Capability $configuration, WebhostingAccount $account, LogMessages $messages): bool
    {
        return true;
    }
}
