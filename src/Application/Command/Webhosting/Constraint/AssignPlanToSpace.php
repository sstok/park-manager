<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class AssignPlanToSpace
{
    private function __construct(
        public PlanId $plan,
        public SpaceId $space,
        public bool $withConstraints = true
    ) {}

    public static function withConstraints(PlanId $plan, SpaceId $space): self
    {
        return new self($plan, $space);
    }

    public static function withoutConstraints(PlanId $plan, SpaceId $space): self
    {
        return new self($plan, $space, false);
    }
}
