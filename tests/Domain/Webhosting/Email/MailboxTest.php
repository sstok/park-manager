<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Email;

use Assert\InvalidArgumentException;
use Lifthill\Component\Common\Domain\Model\ByteSize;
use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * @internal
 */
final class MailboxTest extends TestCase
{
    /** @test */
    public function its_constructable(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $mailbox = new Mailbox(MailboxId::create(), $space, 's.stok', $domainName, new ByteSize(10, 'GiB'), 'BoyThatEscalatedBigLy');

        self::assertSame('s.stok', $mailbox->address);
        self::assertSame($domainName, $mailbox->domainName);
        self::assertSame($space, $mailbox->space);
    }

    /** @test */
    public function it_allows_changing_address_without_a_domain_name_provided(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $mailbox = new Mailbox(MailboxId::create(), $space, 's.stok', $domainName, new ByteSize(10, 'GiB'), 'BoyThatEscalatedBigLy');
        $mailbox->setAddress('info');

        self::assertSame('info', $mailbox->address);
        self::assertSame($domainName, $mailbox->domainName);
        self::assertSame($space, $mailbox->space);
    }

    /** @test */
    public function it_allows_changing_address_domain_name(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));
        $domainName2 = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('rollerscapes', 'net'));

        $mailbox = new Mailbox(MailboxId::create(), $space, 's.stok', $domainName, new ByteSize(10, 'GiB'), 'BoyThatEscalatedBigLy');
        $mailbox->setAddress('info', $domainName2);

        self::assertSame('info', $mailbox->address);
        self::assertSame($domainName2, $mailbox->domainName);
        self::assertSame($space, $mailbox->space);
    }

    /** @test */
    public function it_validates_address(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $this->expectException(RfcComplianceException::class);

        new Mailbox(MailboxId::create(), $space, 's@k..', $domainName, new ByteSize(10, 'GiB'), 'BoyThatEscalatedBigLy');
    }

    /** @test */
    public function it_validates_space_compliance(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $space2 = SpaceRepositoryMock::createSpace('3740c43e-3cf4-49d8-9ee6-4105f5cc9a35');
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space2, new DomainNamePair('park-manager', 'com'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DomainName must be part of the same Space');

        new Mailbox(MailboxId::create(), $space, 'info', $domainName, new ByteSize(10, 'GiB'), 'BoyThatEscalatedBigLy');
    }

    /** @test */
    public function it_validates_no_pattern_is_provided(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mailbox Address cannot be a pattern');

        new Mailbox(MailboxId::create(), $space, 'io*n', $domainName, new ByteSize(10, 'GiB'), 'BoyThatEscalatedBigLy');
    }

    /** @test */
    public function it_validates_no_label_is_provided(): void
    {
        $space = SpaceRepositoryMock::createSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::create(), $space, new DomainNamePair('park-manager', 'com'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Label is not allowed for a Mailbox address');

        new Mailbox(MailboxId::create(), $space, 'info+support', $domainName, new ByteSize(10, 'GiB'), 'BoyThatEscalatedBigLy');
    }
}
