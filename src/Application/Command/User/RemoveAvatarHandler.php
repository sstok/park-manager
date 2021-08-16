<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use League\Flysystem\Filesystem;
use ParkManager\Domain\User\UserRepository;

final class RemoveAvatarHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private Filesystem $avatarStorage
    ) {
    }

    public function __invoke(RemoveAvatar $command): void
    {
        // Verify the user actually exists.
        $this->userRepository->get($command->id);

        $this->avatarStorage->delete($command->id->toString() . '.jpg');
    }
}
