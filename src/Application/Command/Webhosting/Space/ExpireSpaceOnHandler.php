<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceBeingRemoved;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;

final class ExpireSpaceOnHandler
{
    public function __construct(private SpaceRepository $spaceRepository)
    {
    }

    public function __invoke(ExpireSpaceOn $command): void
    {
        $space = $this->spaceRepository->get($command->id);

        if ($space->isMarkedForRemoval()) {
            throw new WebhostingSpaceBeingRemoved($space->primaryDomainLabel);
        }

        $space->markExpirationDate($command->expirationDate);

        $this->spaceRepository->save($space);
    }
}
