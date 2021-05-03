<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\User\Exception\EmailChangeConfirmationRejected;
use ParkManager\Domain\User\UserRepository;

final class ConfirmEmailAddressChangeHandler
{
    private UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(ConfirmEmailAddressChange $command): void
    {
        $user = $this->repository->getByEmailAddressChangeToken($command->token->selector());

        // Cannot use finally here as the exception triggers the global exception handler
        // making the overall process unpredictable.

        try {
            $user->confirmEmailChange($command->token);
            $this->repository->save($user);
        } catch (EmailChangeConfirmationRejected $e) {
            $this->repository->save($user);

            throw $e;
        }
    }
}
