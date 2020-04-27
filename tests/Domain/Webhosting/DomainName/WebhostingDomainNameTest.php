<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\DomainName;

use ParkManager\Domain\Webhosting\DomainName;
use ParkManager\Domain\Webhosting\DomainName\Exception\CannotTransferPrimaryDomainName;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainName;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingDomainNameTest extends TestCase
{
    private const SPACE_ID1 = '374dd50e-9b9f-11e7-9730-acbc32b58315';
    private const SPACE_ID2 = 'cfa42746-a6ac-11e7-bff0-acbc32b58315';

    /** @test */
    public function it_registers_primary_domain_name(): void
    {
        $domainName = new DomainName('example', 'com');
        $domainName2 = new DomainName('example', 'net');
        $space = $this->createSpace(self::SPACE_ID1);
        $space2 = $this->createSpace(self::SPACE_ID2);

        $webhostingDomainName = WebhostingDomainName::registerPrimary($space, $domainName);
        $webhostingDomainName2 = WebhostingDomainName::registerPrimary($space2, $domainName2);

        self::assertNotEquals($webhostingDomainName, $webhostingDomainName2);
        self::assertEquals($domainName, $webhostingDomainName->getDomainName());
        self::assertEquals($domainName2, $webhostingDomainName2->getDomainName());
        self::assertEquals($space, $webhostingDomainName->getSpace());
        self::assertEquals($space2, $webhostingDomainName->getSpace());
        self::assertTrue($webhostingDomainName->isPrimary());
        self::assertTrue($webhostingDomainName2->isPrimary());
    }

    /** @test */
    public function it_registers_secondary_domain_name(): void
    {
        $domainName2 = new DomainName('example', 'net');
        $space = $this->createSpace(self::SPACE_ID1);

        $webhostingDomainName = WebhostingDomainName::registerSecondary($space, $domainName2);

        self::assertEquals($domainName2, $webhostingDomainName->getDomainName());
        self::assertEquals($space, $webhostingDomainName->getSpace());
        self::assertFalse($webhostingDomainName->isPrimary());
    }

    /** @test */
    public function it_can_upgrade_secondary_to_primary(): void
    {
        $domainName = new DomainName('example', 'com');
        $space = $this->createSpace(self::SPACE_ID1);

        $webhostingDomainName = WebhostingDomainName::registerSecondary($space, $domainName);
        $webhostingDomainName->markPrimary();

        self::assertEquals($domainName, $webhostingDomainName->getDomainName());
        self::assertTrue($webhostingDomainName->isPrimary());
    }

    /** @test */
    public function it_can_change_name(): void
    {
        $webhostingDomainName = WebhostingDomainName::registerSecondary(
            $this->createSpace(self::SPACE_ID1),
            new DomainName('example', 'com')
        );

        $webhostingDomainName->changeName($name = new DomainName('example', 'com'));

        self::assertEquals($name, $webhostingDomainName->getDomainName());
    }

    /** @test */
    public function it_can_transfer_secondary_domain_name(): void
    {
        $space2 = $this->createSpace(self::SPACE_ID2);
        $webhostingDomainName = WebhostingDomainName::registerSecondary(
            $this->createSpace(self::SPACE_ID1),
            new DomainName('example', 'com')
        );

        $webhostingDomainName->transferToSpace($space2);

        self::assertEquals($space2, $webhostingDomainName->getSpace());
    }

    /** @test */
    public function it_cannot_transfer_primary_domain_name(): void
    {
        $space2 = $this->createSpace(self::SPACE_ID2);
        $space1 = $this->createSpace(self::SPACE_ID1);
        $webhostingDomainName = WebhostingDomainName::registerPrimary($space1, new DomainName('example', 'com'));

        $this->expectException(CannotTransferPrimaryDomainName::class);
        $this->expectExceptionMessage(
            CannotTransferPrimaryDomainName::of($webhostingDomainName->getId(), $space1->getId(), $space2->getId())->getMessage()
        );

        $webhostingDomainName->transferToSpace($space2);
    }

    private function createSpace(string $id): Space
    {
        $space = $this->createMock(Space::class);
        $space
            ->method('getId')
            ->willReturn(SpaceId::fromString($id));

        return $space;
    }
}
