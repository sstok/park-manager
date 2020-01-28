<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Webhosting\Fixtures;

use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Constraint\Constraint;
use ParkManager\Infrastructure\Webhosting\Constraint\ConstraintApplier;

final class MonthlyTrafficQuotaApplier implements ConstraintApplier
{
    public function apply(Constraint $configuration, Space $space, array $context = []): void
    {
    }
}
