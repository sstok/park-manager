<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\OwnerRepository;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;

final class SwitchSpaceOwnerHandler
{
    private WebhostingSpaceRepository $spaceRepository;
    private OwnerRepository $ownerRepository;

    public function __construct(WebhostingSpaceRepository $spaceRepository, OwnerRepository $ownerRepository)
    {
        $this->spaceRepository = $spaceRepository;
        $this->ownerRepository = $ownerRepository;
    }

    public function __invoke(SwitchSpaceOwner $command): void
    {
        $space = $this->spaceRepository->get($command->space);
        $owner = $this->ownerRepository->get($command->newOwner);

        if ($space->owner === $owner) {
            return;
        }

        $space->switchToOwner($owner);
        $this->spaceRepository->save($space);
    }
}
