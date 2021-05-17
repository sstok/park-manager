<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use Carbon\CarbonImmutable;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerRepository;
use ParkManager\Domain\User\Exception\EmailAddressAlreadyInUse;
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserRepository;

final class RegisterUserHandler
{
    private UserRepository $repository;
    private OwnerRepository $ownerRepository;

    public function __construct(UserRepository $repository, OwnerRepository $ownerRepository)
    {
        $this->repository = $repository;
        $this->ownerRepository = $ownerRepository;
    }

    public function __invoke(RegisterUser $command): void
    {
        try {
            $user = $this->repository->getByEmail($command->email);

            throw new EmailAddressAlreadyInUse($user->id, $command->email);
        } catch (UserNotFound) {
            // No-op
        }

        $user = User::register(
            $command->id,
            $command->email,
            $command->displayName,
            $command->password
        );

        if ($command->requireNewPassword) {
            $user->expirePasswordOn(CarbonImmutable::rawParse('-1 year'));
        }

        $this->repository->save($user);
        $this->ownerRepository->save(Owner::byUser($user));
    }
}
