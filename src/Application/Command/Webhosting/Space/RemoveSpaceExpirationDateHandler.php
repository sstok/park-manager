<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceBeingRemoved;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceIsSuspended;
use ParkManager\Domain\Webhosting\Space\SuspensionLevel;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;

final class RemoveSpaceExpirationDateHandler
{
    private WebhostingSpaceRepository $spaceRepository;

    public function __construct(WebhostingSpaceRepository $spaceRepository)
    {
        $this->spaceRepository = $spaceRepository;
    }

    public function __invoke(RemoveSpaceExpirationDate $command): void
    {
        $space = $this->spaceRepository->get($command->id);

        if ($space->isMarkedForRemoval()) {
            throw new WebhostingSpaceBeingRemoved($space->primaryDomainLabel);
        }

        if (SuspensionLevel::equalsTo($space->accessSuspended, SuspensionLevel::get('LOCKED'))) {
            throw new WebhostingSpaceIsSuspended($space->id, $space->accessSuspended);
        }

        $space->removeExpirationDate();

        $this->spaceRepository->save($space);
    }
}
