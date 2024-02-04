<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\User\UserRepository;

final class RevokeUserRoleHandler
{
    public function __construct(private UserRepository $userRepository) {}

    public function __invoke(RevokeUserRole $command): void
    {
        $user = $this->userRepository->get($command->id);

        foreach ($command->roles as $role) {
            $user->removeRole($role);
        }

        $this->userRepository->save($user);
    }
}
