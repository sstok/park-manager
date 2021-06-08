<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\PlanRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;

final class RemovePlanHandler
{
    public function __construct(
        private PlanRepository $planRepository,
        private WebhostingSpaceRepository $spaceRepository
    ) {
    }

    public function __invoke(RemovePlan $command): void
    {
        $plan = $this->planRepository->get($command->id);
        $spaces = $this->spaceRepository->allWithAssignedPlan($plan->id);

        /** @var Space $space */
        foreach ($spaces as $space) {
            if (! $space->constraints->equals($plan->constraints)) {
                $space->assignCustomConstraints($space->constraints);
            } else {
                $space->assignCustomConstraints($plan->constraints);
            }

            $this->spaceRepository->save($space);
        }

        $this->planRepository->remove($plan);
    }
}
