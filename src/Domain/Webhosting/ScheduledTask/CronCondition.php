<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\ScheduledTask;

use Assert\Assertion;
use Cron\CronExpression;

class CronCondition implements ScheduleCondition
{
    public function __construct(public string $value)
    {
        Assertion::true(CronExpression::isValidExpression($value), 'This value is not a valid CRON expression.');
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
