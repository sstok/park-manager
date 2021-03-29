<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\PlanRepository;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;
use Symfony\Component\Messenger\MessageBusInterface;

final class SyncPlanConstraintsHandler
{
    private PlanRepository $planRepository;
    private WebhostingSpaceRepository $spaceRepository;
    private MessageBusInterface $messageBus;

    public function __construct(PlanRepository $planRepository, WebhostingSpaceRepository $spaceRepository, MessageBusInterface $messageBus)
    {
        $this->planRepository = $planRepository;
        $this->spaceRepository = $spaceRepository;
        $this->messageBus = $messageBus;
    }

    public function __invoke(SyncPlanConstraints $command): void
    {
        $plan = $this->planRepository->get($command->id);

        // The updating of Space constraints should happen async as this might result
        // in heavy IO activity when computing acceptable levels.
        foreach ($this->spaceRepository->allWithAssignedPlan($plan->id) as $space) {
            $this->messageBus->dispatch(AssignPlanToSpace::withConstraints($plan->id, $space->id));
        }
    }
}
