<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\ScheduledTask;

use Assert\Assertion;

class MomentCondition implements ScheduleCondition
{
    /**
     * @param string $value If the moment contains a date, the time with preceding zero (and without seconds) is mandatory.
     *                      Only a time means every day, at this specific time.
     */
    public function __construct(public string $value)
    {
        if (str_contains($value, '-')) {
            Assertion::date($value, 'Y-m-d H:i', 'This value is not a valid moment.');
        } else {
            Assertion::date($value, 'H:i', 'This value is not a valid moment.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
