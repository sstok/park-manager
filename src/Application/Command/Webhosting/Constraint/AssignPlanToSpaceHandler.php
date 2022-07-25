<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Constraint;

use ParkManager\Application\Service\SpaceConstraint\ApplicabilityChecker;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Constraint\PlanRepository;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use ParkManager\Domain\Webhosting\Space\SuspensionLevel;

final class AssignPlanToSpaceHandler
{
    public function __construct(
        private PlanRepository $planRepository,
        private SpaceRepository $spaceRepository,
        private ApplicabilityChecker $applicabilityChecker
    ) {
    }

    public function __invoke(AssignPlanToSpace $command): void
    {
        $plan = $this->planRepository->get($command->plan);
        $space = $this->spaceRepository->get($command->space);

        if (! $command->withConstraints && PlanId::equalsValueOfEntity($command->plan, $space->plan, 'id')) {
            return;
        }

        if (SuspensionLevel::equalsTo($space->accessSuspended, SuspensionLevel::LOCKED)) {
            return;
        }

        if ($command->withConstraints) {
            $constraints = $this->applicabilityChecker->getApplicable($command->space, $plan->constraints);
            $space->assignPlanWithConstraints($plan, $constraints);
        } else {
            $space->assignPlan($plan);
        }

        $this->spaceRepository->save($space);
    }
}
