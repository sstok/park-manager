<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Exception\ConstraintNotRegistered;

final class ConstraintsFactory
{
    private $constraintsByName;

    public function __construct(array $constraintsByName)
    {
        $this->constraintsByName = $constraintsByName;
    }

    public function reconstituteFromStorage(array $constraints): Constraints
    {
        $constraintsInstances = [];

        foreach ($constraints as $name => $configuration) {
            if (! isset($this->constraintsByName[$name])) {
                throw ConstraintNotRegistered::withName($name);
            }

            $constraintsInstances[] = $this->constraintsByName[$name]::reconstituteFromArray($configuration);
        }

        return new Constraints(...$constraintsInstances);
    }
}
