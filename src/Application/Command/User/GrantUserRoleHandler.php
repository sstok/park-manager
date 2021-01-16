<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\User\UserRepository;

final class GrantUserRoleHandler
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function __invoke(GrantUserRole $command): void
    {
        $user = $this->userRepository->get($command->id);

        foreach ($command->roles as $role) {
            $user->addRole($role);
        }

        $this->userRepository->save($user);
    }
}
