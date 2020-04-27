<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Administrator;

use ParkManager\Domain\User\Exception\EmailAddressAlreadyInUse;
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserRepository;

final class RegisterAdministratorHandler
{
    private $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(RegisterAdministrator $command): void
    {
        try {
            $user = $this->repository->getByEmail($command->email);

            throw new EmailAddressAlreadyInUse($user->id);
        } catch (UserNotFound $e) {
            // No-op
        }

        $this->repository->save(
            User::registerAdmin(
                $command->id,
                $command->email,
                $command->displayName,
                $command->password
            )
        );
    }
}
