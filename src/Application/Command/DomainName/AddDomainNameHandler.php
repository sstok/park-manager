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
use ParkManager\Domain\OwnerRepository;

final class AddDomainNameHandler
{
    public function __construct(
        private DomainNameRepository $repository,
        private OwnerRepository $ownerRepository
    ) {
    }

    public function __invoke(AddDomainName $command): void
    {
        try {
            $foundDomain = $this->repository->getByName($command->name);

            throw new DomainNameAlreadyInUse($foundDomain->namePair);
        } catch (DomainNameNotFound) {
            // OK
        }

        $domainName = DomainName::register(
            $command->id,
            $command->name,
            $this->ownerRepository->get($command->owner)
        );

        $this->repository->save($domainName);
    }
}
