<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Organization;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Application\Command\Organization\RemoveOrganization;
use ParkManager\Application\Command\Organization\RemoveOrganizationHandler;
use ParkManager\Application\Service\OwnershipUsageList;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Organization\Exception\CannotRemoveActiveOrganization;
use ParkManager\Domain\Organization\Exception\CannotRemoveInternalOrganization;
use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Owner;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\Organization\OrganizationRepositoryMock;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RemoveOrganizationHandlerTest extends TestCase
{
    private const ORG_ID1 = 'aa0e044c-21ec-4653-998b-d8e8232e8265';
    private const ORG_ID2 = '53969602-2864-4230-929d-0ad35fc26332';

    private OrganizationRepositoryMock $orgRepository;
    private RemoveOrganizationHandler $handler;

    protected function setUp(): void
    {
        $this->orgRepository = new OrganizationRepositoryMock(
            new UserRepositoryMock(),
            [
                new Organization(OrganizationId::fromString(self::ORG_ID1), 'Testing Inc.'),
                $org = new Organization(OrganizationId::fromString(self::ORG_ID2), 'Rollerworks'),
            ]
        );
        $orgOwner = Owner::byOrganization($org);

        $spaceRepository = new SpaceRepositoryMock([SpaceRepositoryMock::createSpace(owner: $orgOwner)]);
        $domainNameRepository = new DomainNameRepositoryMock([
            DomainName::register(
                DomainNameId::fromString('9f2e0b4f-274c-4571-a50a-685f7894fdec'),
                new DomainNamePair('example', 'com'),
                $orgOwner
            ),
        ]);

        $ownershipUsageList = new OwnershipUsageList([
            Space::class => $spaceRepository,
            DomainName::class => $domainNameRepository,
        ]);

        $this->handler = new RemoveOrganizationHandler($this->orgRepository, $ownershipUsageList);
    }

    /** @test */
    public function it_fails_removal_of_internal_organization(): void
    {
        $id = OrganizationId::fromString(OrganizationId::ADMIN_ORG);

        $this->expectExceptionObject(CannotRemoveInternalOrganization::withId($id));

        ($this->handler)(new RemoveOrganization($id));
    }

    /** @test */
    public function it_fails_removal_when_still_assigned_as_owner(): void
    {
        $id = OrganizationId::fromString(self::ORG_ID2);

        try {
            ($this->handler)(new RemoveOrganization($id));

            self::fail('Exception was expected.');
        } catch (CannotRemoveActiveOrganization $e) {
            self::assertSame($id, $e->id);

            // Actual testing of the result is unneeded.
            self::assertArrayHasKey(Space::class, $e->activeEntities);
            self::assertArrayHasKey(DomainName::class, $e->activeEntities);
            self::assertCount(1, $e->activeEntities[Space::class]);
            self::assertCount(1, $e->activeEntities[DomainName::class]);
        }
    }

    /** @test */
    public function it_removes_organization(): void
    {
        $id = OrganizationId::fromString(self::ORG_ID1);

        ($this->handler)(new RemoveOrganization($id));

        $this->orgRepository->assertEntitiesWereRemoved([self::ORG_ID1]);
    }
}
