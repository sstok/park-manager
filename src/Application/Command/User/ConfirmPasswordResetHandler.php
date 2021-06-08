<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Domain\User\UserRepository;

final class ConfirmPasswordResetHandler
{
    public function __construct(private UserRepository $repository)
    {
    }

    public function __invoke(ConfirmPasswordReset $command): void
    {
        $token = $command->token;
        $user = $this->repository->getByPasswordResetToken($token->selector());

        // Cannot use finally here as the exception triggers the global exception handler
        // making the overall process unpredictable.

        try {
            $user->confirmPasswordReset($token, $command->password);
            $this->repository->save($user);
        } catch (PasswordResetTokenNotAccepted $e) {
            $this->repository->save($user);

            throw $e;
        }
    }
}
