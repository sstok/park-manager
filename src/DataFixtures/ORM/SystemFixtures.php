<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Organization\OrganizationRepository;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerRepository;

final class SystemFixtures extends Fixture
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
        private OwnerRepository $ownerRepository
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // XXX This needs to be moved to an installer script as it's ALWAYS needed.
        $this->organizationRepository->save($systemAppOrg = new Organization(OrganizationId::fromString(OrganizationId::SYSTEM_APP), 'SystemApplication'));
        $this->ownerRepository->save(Owner::byOrganization($systemAppOrg));
    }
}
