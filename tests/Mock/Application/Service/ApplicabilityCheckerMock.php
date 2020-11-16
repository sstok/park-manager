<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Application\Service;

use ParkManager\Application\Service\SpaceConstraint\ApplicabilityChecker;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Space\SpaceId;

/**
 * @internal
 */
final class ApplicabilityCheckerMock extends ApplicabilityChecker
{
    public ?SpaceId $mockForId = null;
    public Constraints $mockConstraints;

    public function __construct()
    {
        // no-op
    }

    public function getApplicable(SpaceId $id, Constraints $constraints): Constraints
    {
        if ($this->mockForId !== null && $id->equals($this->mockForId)) {
            return $this->mockConstraints;
        }

        return $constraints;
    }
}
