<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Space\SpaceId;

/**
 * Assign the Constraints to Space, while removing the Plan assignment.
 */
final class AssignConstraintsToSpace
{
    public function __construct(public SpaceId $space, public Constraints $constraints)
    {
    }
}
