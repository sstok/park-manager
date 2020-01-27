<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserRepository;

final class RegisterUserHandler
{
    /** @var UserRepository */
    private $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(RegisterUser $command): void
    {
        $this->repository->save(
            User::register(
                $command->id,
                $command->primaryEmail,
                $command->displayName,
                $command->password
            )
        );
    }
}
