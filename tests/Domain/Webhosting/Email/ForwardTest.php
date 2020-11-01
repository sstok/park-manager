<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Email;

use Assert\InvalidArgumentException;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardId;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * @internal
 */
final class ForwardTest extends TestCase
{
    /** @test */
    public function its_constructable_with_address_destination(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $forward = Forward::toAddress(ForwardId::create(), $space, 'S.stok', $domainName, new EmailAddress('BoyThatEscalatedBigLy@example.com'));

        self::assertSame('s.stok', $forward->address);
        self::assertSame($domainName, $forward->domainName);
        self::assertSame('address:BoyThatEscalatedBigLy@example.com', $forward->destination);
        self::assertSame($space, $forward->space);
        self::assertTrue($forward->active);
    }

    /** @test */
    public function its_constructable_with_script_destination(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $forward = Forward::toScript(ForwardId::create(), $space, 's.stok', $domainName, '/my-site/scripts/stop-spamming-me.php');

        self::assertSame('s.stok', $forward->address);
        self::assertSame($domainName, $forward->domainName);
        self::assertSame('script:/my-site/scripts/stop-spamming-me.php', $forward->destination);
        self::assertSame($space, $forward->space);
    }

    /** @test */
    public function its_constructable_with_address_pattern(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $forward = Forward::toAddress(ForwardId::create(), $space, '*', $domainName, new EmailAddress('info@example.com'));

        self::assertSame('*', $forward->address);
        self::assertSame($domainName, $forward->domainName);
        self::assertSame($space, $forward->space);
    }

    /** @test */
    public function can_be_deactivated(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $forward = Forward::toScript(ForwardId::create(), $space, 's.stok', $domainName, '/my-site/scripts/stop-spamming-me.php');
        $forward->deActivate();

        self::assertFalse($forward->active);

        $forward->activate();

        self::assertTrue($forward->active);
    }

    /** @test */
    public function it_allows_changing_address_without_a_domain_name_provided(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $forward = Forward::toAddress(ForwardId::create(), $space, 's.stok', $domainName, new EmailAddress('info@example.com'));
        $forward->setAddress('info');

        self::assertSame('info', $forward->address);
        self::assertSame($domainName, $forward->domainName);
        self::assertSame($space, $forward->space);
    }

    /** @test */
    public function it_allows_changing_address_domain_name(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));
        $domainName2 = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('rollerscapes', 'net'));

        $forward = Forward::toAddress(ForwardId::create(), $space, 's.stok', $domainName, new EmailAddress('info@example.com'));
        $forward->setAddress('info', $domainName2);

        self::assertSame('info', $forward->address);
        self::assertSame($domainName2, $forward->domainName);
        self::assertSame($space, $forward->space);
        self::assertSame('address:info@example.com', $forward->destination);
    }

    /** @test */
    public function it_allows_changing_destination_to_a_script(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $forward = Forward::toAddress(ForwardId::create(), $space, 's.stok', $domainName, new EmailAddress('info@example.com'));
        $forward->setDestinationToScript('/dev/null');

        self::assertSame('s.stok', $forward->address);
        self::assertSame($space, $forward->space);
        self::assertSame('script:/dev/null', $forward->destination);
    }

    /** @test */
    public function it_allows_changing_destination_to_address(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $forward = Forward::toAddress(ForwardId::create(), $space, 's.stok', $domainName, new EmailAddress('info@example.com'));
        $forward->setDestinationToAddress(new EmailAddress('noreply@example.com'));

        self::assertSame('s.stok', $forward->address);
        self::assertSame($space, $forward->space);
        self::assertSame('address:noreply@example.com', $forward->destination);
    }

    /** @test */
    public function it_validates_address(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $this->expectException(RfcComplianceException::class);

        Forward::toAddress(ForwardId::create(), $space, 's@', $domainName, new EmailAddress('info@example.com'));
    }

    /** @test */
    public function it_validates_destination_address(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $this->expectException(RfcComplianceException::class);

        Forward::toAddress(ForwardId::create(), $space, 's', $domainName, new EmailAddress('"=--WAT--@"=@example.com'));
    }

    /** @test */
    public function it_validates_space_compliance(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $space2 = SpaceRepositoryMock::createSpace('3740c43e-3cf4-49d8-9ee6-4105f5cc9a35');
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space2, new DomainNamePair('park-manager', 'com'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DomainName must be part of the same Space');

        Forward::toAddress(ForwardId::create(), $space, 'info', $domainName, new EmailAddress('info@example.com'));
    }

    /** @test */
    public function it_validates_no_pattern_is_provided_destination(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Destination cannot be a pattern');

        Forward::toAddress(ForwardId::create(), $space, 'info', $domainName, new EmailAddress('*@example.com'));
    }

    /** @test */
    public function it_validates_no_label_is_provided(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Label is not allowed for a Forward address');

        Forward::toAddress(ForwardId::create(), $space, 'info+support', $domainName, new EmailAddress('info@example.com'));
    }
}
