<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\DomainName;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Application\Command\DomainName\AddDomainNameToSpace;
use ParkManager\Application\Command\DomainName\AddDomainNameToSpaceHandler;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Owner;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AddDomainNameToSpaceHandlerTest extends TestCase
{
    private const USER_ID1 = UserRepositoryMock::USER_ID1;
    private const USER_ID2 = '6f8151b9-b670-46eb-9c4a-5a77e6aba0e3';

    private const SPACE_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const SPACE_ID2 = '696d345c-a5e1-11e7-9856-acbc32b58315';

    private const EXISTING_DOMAIN1 = 'dce04e6d-a4d9-47aa-9be0-bda8bab41b63';

    private DomainNameRepositoryMock $domainNameRepository;
    private SpaceRepositoryMock $spaceRepository;

    protected function setUp(): void
    {
        $owner1 = Owner::byUser(UserRepositoryMock::createUser(id: self::USER_ID1));
        $owner2 = Owner::byUser(UserRepositoryMock::createUser('janet2@example.com', id: self::USER_ID2));

        $this->spaceRepository = new SpaceRepositoryMock([
            $space1 = SpaceRepositoryMock::createSpace(self::SPACE_ID1, $owner1, domainName: $domainName1 = new DomainNamePair('example', 'com')),
            $space2 = SpaceRepositoryMock::createSpace(self::SPACE_ID2, $owner2, domainName: $domainName2 = new DomainNamePair('example', 'net')),
        ]);

        $this->domainNameRepository = new DomainNameRepositoryMock([
            DomainName::registerForSpace(DomainNameId::fromString('65bfb2e8-fb6c-49a2-8d2c-c2a6695f19ea'), $space1, $domainName1),
            DomainName::registerForSpace(DomainNameId::fromString('e0e7ec96-73de-4023-88f6-3ac854213a6c'), $space2, $domainName2),
            DomainName::register(DomainNameId::fromString(self::EXISTING_DOMAIN1), new DomainNamePair('example', 'international'), $owner1),
        ]);

        $this->spaceRepository->save($space1);
        $this->spaceRepository->save($space2);
        $this->spaceRepository->resetRecordingState();
    }

    /** @test */
    public function it_handles_registration_of_not_existing_domain_name(): void
    {
        $handler = new AddDomainNameToSpaceHandler($this->domainNameRepository, $this->spaceRepository);

        $domainName = new DomainNamePair('example', 'org');
        $handler(new AddDomainNameToSpace($domainName, SpaceId::fromString(self::SPACE_ID1), primary: false));

        $this->spaceRepository->assertHasEntity(self::SPACE_ID1, static fn (Space $space): bool => $space->primaryDomainLabel !== $domainName);
        $this->domainNameRepository->assertEntitiesCountWasSaved(1);
        $this->domainNameRepository->assertHasEntityThat(static function (DomainName $storedDomainName) use ($domainName): bool {
            if ($domainName->toString() !== $storedDomainName->namePair->toString()) {
                return false;
            }

            if ($storedDomainName->isPrimary()) {
                return false;
            }

            return $storedDomainName->space->id->equals(SpaceId::fromString(self::SPACE_ID1));
        });
    }

    /** @test */
    public function it_handles_registration_of_existing_domain_name(): void
    {
        $handler = new AddDomainNameToSpaceHandler($this->domainNameRepository, $this->spaceRepository);

        $domainName = new DomainNamePair('example', 'international');
        $handler(new AddDomainNameToSpace($domainName, SpaceId::fromString(self::SPACE_ID1), primary: false));

        $this->spaceRepository->assertHasEntity(self::SPACE_ID1, static fn (Space $space): bool => $space->primaryDomainLabel !== $domainName);
        $this->domainNameRepository->assertEntitiesCountWasSaved(1);
        $this->domainNameRepository->assertHasEntity(self::EXISTING_DOMAIN1, static function (DomainName $storedDomainName): bool {
            if ($storedDomainName->isPrimary()) {
                return false;
            }

            return $storedDomainName->space->id->equals(SpaceId::fromString(self::SPACE_ID1));
        });
    }

    /** @test */
    public function it_handles_registration_of_new_primary_domain_name(): void
    {
        $handler = new AddDomainNameToSpaceHandler($this->domainNameRepository, $this->spaceRepository);

        $domainName = new DomainNamePair('example', 'international');
        $handler(new AddDomainNameToSpace($domainName, SpaceId::fromString(self::SPACE_ID1), primary: true));

        $this->spaceRepository->assertHasEntity(self::SPACE_ID1, static fn (Space $space): bool => $space->primaryDomainLabel->equals($domainName));
        $this->domainNameRepository->assertEntitiesCountWasSaved(2); // Internally the first one is changed from primary
        $this->domainNameRepository->assertHasEntity(self::EXISTING_DOMAIN1, static function (DomainName $storedDomainName): bool {
            if (! $storedDomainName->isPrimary()) {
                return false;
            }

            return $storedDomainName->space->id->equals(SpaceId::fromString(self::SPACE_ID1));
        });
    }
}
