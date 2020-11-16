<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\User\UserRepository;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;

final class SwitchSpaceOwnerHandler
{
    private WebhostingSpaceRepository $spaceRepository;
    private UserRepository $userRepository;

    public function __construct(WebhostingSpaceRepository $spaceRepository, UserRepository $userRepository)
    {
        $this->spaceRepository = $spaceRepository;
        $this->userRepository = $userRepository;
    }

    public function __invoke(SwitchSpaceOwner $command): void
    {
        $space = $this->spaceRepository->get($command->space);
        $owner = $command->newOwner === null ? null : $this->userRepository->get($command->newOwner);

        if ($space->owner === $owner) {
            return;
        }

        $space->switchOwner($owner);
        $this->spaceRepository->save($space);
    }
}
