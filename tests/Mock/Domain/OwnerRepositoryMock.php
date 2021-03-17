<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain;

use ParkManager\Domain\Exception\OwnerNotFound;
use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Organization\OrganizationRepository;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\OwnerRepository;

final class OwnerRepositoryMock implements OwnerRepository
{
    use MockRepository {
        __construct as construct;
    }

    public function __construct(array $initialEntities = [], ?OrganizationRepository $organizationRepository = null)
    {
        if ($organizationRepository) {
            $adminOrganization = $organizationRepository->get(OrganizationId::fromString(OrganizationId::ADMIN_ORG));
        } else {
            $adminOrganization = new Organization(OrganizationId::fromString(OrganizationId::ADMIN_ORG), 'Administrators');
        }

        $initialEntities[] = Owner::byOrganization($adminOrganization);
        $this->construct($initialEntities);
    }

    protected function throwOnNotFound($key): void
    {
        throw OwnerNotFound::withId($key);
    }

    public function get(OwnerId $id): Owner
    {
        return $this->mockDoGetById($id);
    }

    public function save(Owner $owner): void
    {
        $this->mockDoSave($owner);
    }

    public function getAdminOrganization(): Owner
    {
        return $this->get(OwnerId::fromString(OrganizationId::ADMIN_ORG));
    }
}
