<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\UseCase\Administrator;

use ParkManager\Bundle\CoreBundle\Event\UserPasswordWasChanged;
use ParkManager\Bundle\CoreBundle\Model\Administrator\AdministratorRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ChangePasswordHandler
{
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(AdministratorRepository $repository, EventDispatcherInterface $eventDispatcher)
    {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(ChangePassword $command): void
    {
        $administrator = $this->repository->get($command->id);
        $administrator->changePassword($command->password);

        $this->eventDispatcher->dispatch(new UserPasswordWasChanged($command->id->toString(), $command->password));

        $this->repository->save($administrator);
    }
}
