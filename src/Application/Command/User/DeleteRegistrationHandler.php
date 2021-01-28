<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\User\Exception\CannotRemoveSuperAdministrator;
use ParkManager\Domain\User\UserRepository;

final class DeleteRegistrationHandler
{
    private UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(DeleteRegistration $command): void
    {
        $user = $this->repository->get($command->id());

        if ($user->hasRole('ROLE_SUPER_ADMIN')) {
            throw new CannotRemoveSuperAdministrator($user->id);
        }

        $this->repository->remove($user);
    }
}
