<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\DomainName;

use ParkManager\Application\Command\DomainName\AddDomainName;
use ParkManager\Application\Command\DomainName\AddDomainNameHandler;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerId;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\OwnerRepositoryMock;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AddDomainNameHandlerTest extends TestCase
{
    private DomainNameRepositoryMock $repository;
    private OwnerRepositoryMock $ownerRepository;
    private AddDomainNameHandler $handler;

    protected function setUp(): void
    {
        $this->ownerRepository = new OwnerRepositoryMock([$owner = Owner::byUser(UserRepositoryMock::createUser())]);
        $this->repository = new DomainNameRepositoryMock([$this->createExistingDomainWithOwner(new DomainNamePair('example', 'com'), $owner)]);

        $this->handler = new AddDomainNameHandler($this->repository, $this->ownerRepository);
    }

    private function createExistingDomainWithOwner(DomainNamePair $domainName, Owner $owner): DomainName
    {
        return DomainName::register(DomainNameId::fromString('10abb1db-6e93-4dfc-9ba1-cdd46a225657'), $domainName, $owner);
    }

    /** @test */
    public function handles_domain_registration_without_already_existing(): void
    {
        ($this->handler)(AddDomainName::with('e7621ab3-d543-4405-848b-eaf5b85a7f88', UserRepositoryMock::USER_ID1, 'park-manager', 'com'));

        $this->repository->assertEntitiesCountWasSaved(1);
        $this->repository->assertHasEntityThat(static function (DomainName $domainName) {
            if (! $domainName->id->equals(DomainNameId::fromString('e7621ab3-d543-4405-848b-eaf5b85a7f88'))) {
                return false;
            }

            if ($domainName->space !== null) {
                return false;
            }

            if (! OwnerId::equalsValueOfEntity(OwnerId::fromString(UserRepositoryMock::USER_ID1), $domainName->owner, 'id')) {
                return false;
            }

            return $domainName->namePair->equals(new DomainNamePair('park-manager', 'com'));
        });
    }

    /** @test */
    public function handles_domain_registration_already_existing(): void
    {
        $this->expectExceptionObject(new DomainNameAlreadyInUse(new DomainNamePair('example', 'com')));
        $this->expectExceptionMessageMatches('/is already in use\.$/');

        ($this->handler)(AddDomainName::with('e7621ab3-d543-4405-848b-eaf5b85a7f88', OrganizationId::ADMIN_ORG, 'example', 'com'));
    }
}
