<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint;

use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraint;

final class FtpUserCount implements Constraint
{
    private $limit;

    public function __construct(int $limit)
    {
        $this->limit = $limit;
    }

    public function configuration(): array
    {
        return ['limit' => $this->limit];
    }

    public static function reconstituteFromArray(array $from): self
    {
        return new self($from['limit']);
    }
}
