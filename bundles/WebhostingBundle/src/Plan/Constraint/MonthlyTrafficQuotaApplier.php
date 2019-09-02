<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Plan\Constraint;

use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraint;
use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintApplier;

final class MonthlyTrafficQuotaApplier implements ConstraintApplier
{
    public function apply(Constraint $configuration, WebhostingAccount $account, array $context = []): void
    {
    }
}
