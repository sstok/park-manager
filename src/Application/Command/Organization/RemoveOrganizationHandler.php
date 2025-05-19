<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Organization;

use ParkManager\Application\Service\OwnershipUsageList;
use ParkManager\Domain\Organization\Exception\CannotRemoveActiveOrganization;
use ParkManager\Domain\Organization\Exception\CannotRemoveInternalOrganization;
use ParkManager\Domain\Organization\OrganizationRepository;
use ParkManager\Domain\OwnerId;

final class RemoveOrganizationHandler
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
        private OwnershipUsageList $ownershipUsageList
    ) {
    }

    public function __invoke(RemoveOrganization $command): void
    {
        $organization = $this->organizationRepository->get($command->id);

        if ($organization->isInternal()) {
            throw CannotRemoveInternalOrganization::withId($command->id);
        }

        $ownerId = OwnerId::fromString($organization->id->toString());

        if ($this->ownershipUsageList->isAnyAssignedTo($ownerId)) {
            throw new CannotRemoveActiveOrganization($command->id, $this->ownershipUsageList->getByProvider($ownerId));
        }

        $this->organizationRepository->remove($organization);
    }
}
