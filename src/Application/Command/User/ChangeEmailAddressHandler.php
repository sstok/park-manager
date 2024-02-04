<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\User\Exception\EmailAddressAlreadyInUse;
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\UserRepository;

final class ChangeEmailAddressHandler
{
    public function __construct(private UserRepository $userRepository) {}

    public function __invoke(ChangeEmailAddress $command): void
    {
        $user = $this->userRepository->get($command->id);

        if ($user->email->equals($command->emailAddress)) {
            return;
        }

        try {
            $existing = $this->userRepository->getByEmail($command->emailAddress);

            // It's possible only the name, casing or label is changed.
            // But the effective address is still has the same user.
            if (! $existing->id->equals($user->id)) {
                throw new EmailAddressAlreadyInUse($existing->id, $command->emailAddress);
            }
        } catch (UserNotFound) {
            // no-op.
        }

        $user->changeEmail($command->emailAddress);

        $this->userRepository->save($user);
    }
}
