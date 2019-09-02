<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Plan;

use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Exception\ConstraintNotRegistered;

final class ConstraintsFactory
{
    private $constraintsById;

    public function __construct(array $constraintsById)
    {
        $this->constraintsById = $constraintsById;
    }

    /**
     * Reconstitutes a Constraints set from storage.
     */
    public function reconstituteFromStorage(array $constraints): Constraints
    {
        $constraintsInstances = [];

        foreach ($constraints as $id => $configuration) {
            if (! isset($this->constraintsById[$id])) {
                throw ConstraintNotRegistered::withId($id);
            }

            $constraintsInstances[] = $this->constraintsById[$id]::reconstituteFromArray($configuration);
        }

        return new Constraints(...$constraintsInstances);
    }
}
