<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Constraint\Constraints;

use ParkManager\Domain\Webhosting\Constraint\Constraint;

final class MonthlyTrafficQuota implements Constraint
{
    private $quota;

    public function __construct(string $quota)
    {
        $this->quota = $quota;
    }

    public function configuration(): array
    {
        return ['quota' => $this->quota];
    }

    public static function reconstituteFromArray(array $from): self
    {
        return new self($from['quota']);
    }
}
