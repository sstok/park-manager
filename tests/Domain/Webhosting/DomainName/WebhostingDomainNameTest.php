<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\DomainName;

use ParkManager\Domain\Webhosting\Account\WebhostingAccount;
use ParkManager\Domain\Webhosting\Account\WebhostingAccountId;
use ParkManager\Domain\Webhosting\DomainName;
use ParkManager\Domain\Webhosting\DomainName\Exception\CannotTransferPrimaryDomainName;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainName;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingDomainNameTest extends TestCase
{
    private const ACCOUNT_ID1 = '374dd50e-9b9f-11e7-9730-acbc32b58315';
    private const ACCOUNT_ID2 = 'cfa42746-a6ac-11e7-bff0-acbc32b58315';

    /** @test */
    public function it_registers_primary_domain_name(): void
    {
        $domainName = new DomainName('example', 'com');
        $domainName2 = new DomainName('example', 'net');
        $account = $this->createAccount(self::ACCOUNT_ID1);
        $account2 = $this->createAccount(self::ACCOUNT_ID2);

        $webhostingDomainName = WebhostingDomainName::registerPrimary($account, $domainName);
        $webhostingDomainName2 = WebhostingDomainName::registerPrimary($account2, $domainName2);

        static::assertNotEquals($webhostingDomainName, $webhostingDomainName2);
        static::assertEquals($domainName, $webhostingDomainName->getDomainName());
        static::assertEquals($domainName2, $webhostingDomainName2->getDomainName());
        static::assertEquals($account, $webhostingDomainName->getAccount());
        static::assertEquals($account2, $webhostingDomainName->getAccount());
        static::assertTrue($webhostingDomainName->isPrimary());
        static::assertTrue($webhostingDomainName2->isPrimary());
    }

    /** @test */
    public function it_registers_secondary_domain_name(): void
    {
        $domainName2 = new DomainName('example', 'net');
        $account = $this->createAccount(self::ACCOUNT_ID1);

        $webhostingDomainName = WebhostingDomainName::registerSecondary($account, $domainName2);

        static::assertEquals($domainName2, $webhostingDomainName->getDomainName());
        static::assertEquals($account, $webhostingDomainName->getAccount());
        static::assertFalse($webhostingDomainName->isPrimary());
    }

    /** @test */
    public function it_can_upgrade_secondary_to_primary(): void
    {
        $domainName = new DomainName('example', 'com');
        $account = $this->createAccount(self::ACCOUNT_ID1);

        $webhostingDomainName = WebhostingDomainName::registerSecondary($account, $domainName);
        $webhostingDomainName->markPrimary();

        static::assertEquals($domainName, $webhostingDomainName->getDomainName());
        static::assertTrue($webhostingDomainName->isPrimary());
    }

    /** @test */
    public function it_can_change_name(): void
    {
        $webhostingDomainName = WebhostingDomainName::registerSecondary(
            $this->createAccount(self::ACCOUNT_ID1),
            new DomainName('example', 'com')
        );

        $webhostingDomainName->changeName($name = new DomainName('example', 'com'));

        static::assertEquals($name, $webhostingDomainName->getDomainName());
    }

    /** @test */
    public function it_can_transfer_secondary_domain_name(): void
    {
        $account2 = $this->createAccount(self::ACCOUNT_ID2);
        $webhostingDomainName = WebhostingDomainName::registerSecondary(
            $this->createAccount(self::ACCOUNT_ID1),
            new DomainName('example', 'com')
        );

        $webhostingDomainName->transferToAccount($account2);

        static::assertEquals($account2, $webhostingDomainName->getAccount());
    }

    /** @test */
    public function it_cannot_transfer_primary_domain_name(): void
    {
        $account2 = $this->createAccount(self::ACCOUNT_ID2);
        $account1 = $this->createAccount(self::ACCOUNT_ID1);
        $webhostingDomainName = WebhostingDomainName::registerPrimary($account1, new DomainName('example', 'com'));

        $this->expectException(CannotTransferPrimaryDomainName::class);
        $this->expectExceptionMessage(
            CannotTransferPrimaryDomainName::of($webhostingDomainName->getId(), $account1->getId(), $account2->getId())->getMessage()
        );

        $webhostingDomainName->transferToAccount($account2);
    }

    private function createAccount(string $id): WebhostingAccount
    {
        $account = $this->createMock(WebhostingAccount::class);
        $account
            ->expects(static::any())
            ->method('getId')
            ->willReturn(WebhostingAccountId::fromString($id));

        return $account;
    }
}
