<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Webhosting\Fixtures;

use ParkManager\Domain\Webhosting\Account\WebhostingAccountId;
use ParkManager\Domain\Webhosting\Plan\Constraint;
use ParkManager\Infrastructure\Webhosting\Constraint\ConstraintValidator;

final class DenyingValidator implements ConstraintValidator
{
    private $accountId;

    public function validate(WebhostingAccountId $accountId, Constraint $constraint, array $context = []): void
    {
    }
}
