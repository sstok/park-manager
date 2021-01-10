<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Administrator;

use Carbon\CarbonImmutable;
use ParkManager\Domain\User\Exception\EmailAddressAlreadyInUse;
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserRepository;

final class RegisterAdministratorHandler
{
    private UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(RegisterAdministrator $command): void
    {
        try {
            $user = $this->repository->getByEmail($command->email);

            throw new EmailAddressAlreadyInUse($user->id, $command->email);
        } catch (UserNotFound $e) {
            // No-op
        }

        $admin = User::registerAdmin(
            $command->id,
            $command->email,
            $command->displayName,
            $command->password
        );

        if ($command->superAdmin) {
            $admin->addRole('ROLE_SUPER_ADMIN');
        }

        if ($command->requireNewPassword) {
            $admin->expirePasswordOn(CarbonImmutable::rawParse('-1 year'));
        }

        $this->repository->save($admin);
    }
}
