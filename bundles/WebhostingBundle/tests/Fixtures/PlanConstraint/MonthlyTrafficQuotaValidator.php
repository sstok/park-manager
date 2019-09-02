<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint;

use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraint;
use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintValidator;

final class MonthlyTrafficQuotaValidator implements ConstraintValidator
{
    public function validate(WebhostingAccountId $accountId, Constraint $constraint, array $context = []): void
    {
    }
}
