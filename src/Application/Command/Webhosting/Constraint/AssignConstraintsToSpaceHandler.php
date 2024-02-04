<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Constraint;

use ParkManager\Application\Service\SpaceConstraint\ApplicabilityChecker;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use ParkManager\Domain\Webhosting\Space\SuspensionLevel;

final class AssignConstraintsToSpaceHandler
{
    public function __construct(
        private SpaceRepository $spaceRepository,
        private ApplicabilityChecker $applicabilityChecker
    ) {}

    public function __invoke(AssignConstraintsToSpace $command): void
    {
        $space = $this->spaceRepository->get($command->space);

        if ($space->plan === null && $space->constraints->equals($command->constraints)) {
            return;
        }

        if (SuspensionLevel::equalsTo($space->accessSuspended, SuspensionLevel::LOCKED)) {
            return;
        }

        $constraints = $this->applicabilityChecker->getApplicable($command->space, $command->constraints);
        $space->assignCustomConstraints($constraints);

        $this->spaceRepository->save($space);
    }
}
