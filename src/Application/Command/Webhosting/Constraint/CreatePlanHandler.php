<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanRepository;

final class CreatePlanHandler
{
    public function __construct(private PlanRepository $planRepository) {}

    public function __invoke(CreatePlan $command): void
    {
        $plan = new Plan($command->id, $command->constraints);
        $plan->withMetadata($command->metadata);

        $this->planRepository->save($plan);
    }
}
