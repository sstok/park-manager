<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use Carbon\CarbonImmutable;
use ParkManager\Application\Event\UserPasswordWasChanged;
use ParkManager\Domain\User\UserRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ChangePasswordHandler
{
    public function __construct(
        private UserRepository $repository,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(ChangeUserPassword $command): void
    {
        $user = $this->repository->get($command->id);
        $user->changePassword($command->password);

        if ($command->temporary) {
            $user->expirePasswordOn(CarbonImmutable::yesterday());
        }

        $this->eventDispatcher->dispatch(new UserPasswordWasChanged($command->id->toString(), $command->password));

        $this->repository->save($user);
    }
}
