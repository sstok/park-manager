<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\DomainName;

use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\DomainName\Exception\DomainNameNotFound;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;

final class AddDomainNameToSpaceHandler
{
    public function __construct(
        private DomainNameRepository $domainNameRepository,
        private SpaceRepository $spaceRepository
    ) {}

    public function __invoke(AddDomainNameToSpace $command): void
    {
        $space = $this->spaceRepository->get($command->space);

        try {
            $domainName = $this->domainNameRepository->getByName($command->name);
            $domainName->transferToSpace($space, $command->primary);
        } catch (DomainNameNotFound) {
            $domainName = $command->primary ?
                DomainName::registerForSpace(DomainNameId::create(), $space, $command->name) :
                DomainName::registerSecondaryForSpace(DomainNameId::create(), $space, $command->name);
        }

        $this->domainNameRepository->save($domainName);
        $this->spaceRepository->save($space);
    }
}
