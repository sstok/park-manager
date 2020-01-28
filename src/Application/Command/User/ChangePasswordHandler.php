<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Application\Event\UserPasswordWasChanged;
use ParkManager\Domain\User\UserRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ChangePasswordHandler
{
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(UserRepository $repository, EventDispatcherInterface $eventDispatcher)
    {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(ChangeUserPassword $command): void
    {
        $user = $this->repository->get($command->id());
        $user->changePassword($command->password());

        $this->eventDispatcher->dispatch(new UserPasswordWasChanged($command->id()->toString(), $command->password()));

        $this->repository->save($user);
    }
}
