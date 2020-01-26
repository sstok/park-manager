<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Client;

use ParkManager\Application\Event\UserPasswordWasChanged;
use ParkManager\Domain\Client\ClientRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ChangeClientPasswordHandler
{
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(ClientRepository $repository, EventDispatcherInterface $eventDispatcher)
    {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(ChangeClientPassword $command): void
    {
        $client = $this->repository->get($command->id());
        $client->changePassword($command->password());

        $this->eventDispatcher->dispatch(new UserPasswordWasChanged($command->id()->toString(), $command->password()));

        $this->repository->save($client);
    }
}
