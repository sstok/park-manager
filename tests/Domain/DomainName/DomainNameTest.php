<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\DomainName;

use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\DomainName\Exception\CannotAssignDomainNameWithDifferentOwner;
use ParkManager\Domain\DomainName\Exception\CannotTransferPrimaryDomainName;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Owner;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DomainNameTest extends TestCase
{
    private const SPACE_ID1 = '374dd50e-9b9f-11e7-9730-acbc32b58315';
    private const SPACE_ID2 = 'cfa42746-a6ac-11e7-bff0-acbc32b58315';

    /** @test */
    public function it_registers_primary_domain_name(): void
    {
        $domainName = new DomainNamePair('example', 'com');
        $domainName2 = new DomainNamePair('example', 'net');
        $space = $this->createSpace(self::SPACE_ID1);
        $space2 = $this->createSpace(self::SPACE_ID2);

        $webhostingDomainName = DomainName::registerForSpace(DomainNameId::create(), $space, $domainName);
        $webhostingDomainName2 = DomainName::registerForSpace(DomainNameId::create(), $space2, $domainName2);

        self::assertNotEquals($webhostingDomainName, $webhostingDomainName2);
        self::assertEquals($domainName, $webhostingDomainName->namePair);
        self::assertEquals($domainName2, $webhostingDomainName2->namePair);
        self::assertEquals($space, $webhostingDomainName->space);
        self::assertEquals($space2, $webhostingDomainName2->space);
        self::assertTrue($webhostingDomainName->primary);
        self::assertTrue($webhostingDomainName2->primary);
    }

    private function createSpace(string $id, ?Owner $owner = null): Space
    {
        return SpaceRepositoryMock::createSpace($id, $owner);
    }

    /** @test */
    public function it_registers_secondary_domain_name(): void
    {
        $domainName2 = new DomainNamePair('example', 'net');
        $space = $this->createSpace(self::SPACE_ID1);

        $webhostingDomainName = DomainName::registerSecondaryForSpace(DomainNameId::create(), $space, $domainName2);

        self::assertEquals($domainName2, $webhostingDomainName->namePair);
        self::assertEquals($space, $webhostingDomainName->space);
        self::assertFalse($webhostingDomainName->primary);
    }

    /** @test */
    public function it_can_upgrade_secondary_to_primary(): void
    {
        $domainName = new DomainNamePair('example', 'com');
        $space = $this->createSpace(self::SPACE_ID1);

        $webhostingDomainName = DomainName::registerSecondaryForSpace(DomainNameId::create(), $space, $domainName);
        $webhostingDomainName->markPrimary();

        self::assertEquals($domainName, $webhostingDomainName->namePair);
        self::assertTrue($webhostingDomainName->primary);
    }

    /** @test */
    public function it_can_transfer_secondary_domain_name(): void
    {
        $space2 = $this->createSpace(self::SPACE_ID2);
        $webhostingDomainName = DomainName::registerSecondaryForSpace(
            DomainNameId::create(),
            $this->createSpace(self::SPACE_ID1),
            new DomainNamePair('example', 'com')
        );

        $webhostingDomainName->transferToSpace($space2);

        self::assertEquals($space2, $webhostingDomainName->space);
        self::assertFalse($webhostingDomainName->primary);
    }

    /** @test */
    public function it_can_transfer_primary_domain_name_without_space_and_same_owner(): void
    {
        $owner = Owner::byUser(User::register(UserId::fromString('27758a8c-8731-419d-9470-7a2512396a08'), new EmailAddress('mitch@example.com'), 'Mitchel', 'Nope'));
        $space2 = $this->createSpace(self::SPACE_ID2, $owner);

        $webhostingDomainName = DomainName::register(
            DomainNameId::create(),
            new DomainNamePair('example', 'com'),
            $owner
        );

        $webhostingDomainName->transferToSpace($space2, true);

        self::assertEquals($space2, $webhostingDomainName->space);
        self::assertTrue($webhostingDomainName->primary);
    }

    /** @test */
    public function it_cannot_transfer_primary_domain_name(): void
    {
        $space2 = $this->createSpace(self::SPACE_ID2);
        $space1 = $this->createSpace(self::SPACE_ID1);
        $webhostingDomainName = DomainName::registerForSpace(DomainNameId::create(), $space1, new DomainNamePair('example', 'com'));

        $this->expectExceptionObject(new CannotTransferPrimaryDomainName($webhostingDomainName->namePair, $space1->id, $space2->id));

        $webhostingDomainName->transferToSpace($space2);
    }

    /** @test */
    public function it_cannot_transfer_with_different_owner(): void
    {
        $space1 = $this->createSpace(self::SPACE_ID1, Owner::byUser($this->createUser('e666bf16-7eb5-4473-bdbe-c6bc8b64e90f')));
        $space2 = $this->createSpace(self::SPACE_ID2, Owner::byUser($this->createUser('27758a8c-8731-419d-9470-7a2512396a08')));

        $webhostingDomainName = DomainName::registerSecondaryForSpace(DomainNameId::create(), $space1, new DomainNamePair('example', 'com'));

        $this->expectExceptionObject(CannotAssignDomainNameWithDifferentOwner::fromSpace($webhostingDomainName->namePair, $space1->id, $space2->id));
        $this->expectExceptionMessage('Domain name "example.com" of Space 374dd50e-9b9f-11e7-9730-acbc32b58315 does not have the same owner as Space cfa42746-a6ac-11e7-bff0-acbc32b58315.');

        $webhostingDomainName->transferToSpace($space2);
    }

    private function createUser(string $id): User
    {
        return User::register(UserId::fromString($id), new EmailAddress('mitch@example.com'), 'Mitchel', 'Nope');
    }

    /** @test */
    public function it_cannot_assign_with_different_owner(): void
    {
        $owner = Owner::byUser($this->createUser('e666bf16-7eb5-4473-bdbe-c6bc8b64e90f'));
        $space = $this->createSpace(self::SPACE_ID2, Owner::byUser($this->createUser('27758a8c-8731-419d-9470-7a2512396a08')));

        $webhostingDomainName = DomainName::register(
            DomainNameId::create(),
            new DomainNamePair('example', 'com'),
            $owner
        );

        $this->expectExceptionObject(CannotAssignDomainNameWithDifferentOwner::toSpace($webhostingDomainName->namePair, $space->id));
        $this->expectExceptionMessage('Domain name "example.com" does not have the same owner as Space "cfa42746-a6ac-11e7-bff0-acbc32b58315".');

        $webhostingDomainName->transferToSpace($space);
    }
}
