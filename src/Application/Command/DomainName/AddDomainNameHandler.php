<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\DomainName;

use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Domain\DomainName\Exception\DomainNameNotFound;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\User\UserRepository;

final class AddDomainNameHandler
{
    private DomainNameRepository $repository;
    private UserRepository $userRepository;

    public function __construct(DomainNameRepository $repository, UserRepository $userRepository)
    {
        $this->repository = $repository;
        $this->userRepository = $userRepository;
    }

    public function __invoke(AddDomainName $command): void
    {
        try {
            $foundDomain = $this->repository->getByName($command->name);
            $sameOwner = UserId::equalsValue($foundDomain->owner, $command->user, 'id');

            throw new DomainNameAlreadyInUse($command->name, $sameOwner);
        } catch (DomainNameNotFound $e) {
            // OK
        }

        $domainName = DomainName::register(
            $command->id,
            $command->name,
            $command->user ? $this->userRepository->get($command->user) : null
        );

        $this->repository->save($domainName);
    }
}
