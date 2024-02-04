<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\DomainName;

use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\OwnerRepository;

final class AssignDomainNameToOwnerHandler
{
    public function __construct(
        private DomainNameRepository $domainNameRepository,
        private OwnerRepository $ownerRepository
    ) {}

    public function __invoke(AssignDomainNameToOwner $command): void
    {
        $domainName = $this->domainNameRepository->get($command->id);
        $owner = $this->ownerRepository->get($command->owner);

        $domainName->transferToOwner($owner);

        $this->domainNameRepository->save($domainName);
    }
}
