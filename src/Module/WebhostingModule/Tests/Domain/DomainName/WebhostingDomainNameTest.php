<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Tests\Domain\DomainName;

use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\DomainName;
use ParkManager\Module\WebhostingModule\Domain\DomainName\Exception\CannotTransferPrimaryDomainName;
use ParkManager\Module\WebhostingModule\Domain\DomainName\WebhostingDomainName;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingDomainNameTest extends TestCase
{
    private const ACCOUNT_ID1 = '374dd50e-9b9f-11e7-9730-acbc32b58315';
    private const ACCOUNT_ID2 = 'cfa42746-a6ac-11e7-bff0-acbc32b58315';

    /** @test */
    public function it_registers_primary_domainName(): void
    {
        $domainName  = new DomainName('example', 'com');
        $domainName2 = new DomainName('example', 'net');
        $account     = $this->createAccount(self::ACCOUNT_ID1);
        $account2    = $this->createAccount(self::ACCOUNT_ID2);

        $webhostingDomainName  = WebhostingDomainName::registerPrimary($account, $domainName);
        $webhostingDomainName2 = WebhostingDomainName::registerPrimary($account2, $domainName2);

        self::assertNotEquals($webhostingDomainName, $webhostingDomainName2);
        self::assertEquals($domainName, $webhostingDomainName->domainName());
        self::assertEquals($domainName2, $webhostingDomainName2->domainName());
        self::assertEquals($account, $webhostingDomainName->account());
        self::assertEquals($account2, $webhostingDomainName->account());
        self::assertTrue($webhostingDomainName->isPrimary());
        self::assertTrue($webhostingDomainName2->isPrimary());
    }

    /** @test */
    public function it_registers_secondary_domainName(): void
    {
        $domainName2 = new DomainName('example', 'net');
        $account     = $this->createAccount(self::ACCOUNT_ID1);

        $webhostingDomainName = WebhostingDomainName::registerSecondary($account, $domainName2);

        self::assertEquals($domainName2, $webhostingDomainName->domainName());
        self::assertEquals($account, $webhostingDomainName->account());
        self::assertFalse($webhostingDomainName->isPrimary());
    }

    /** @test */
    public function it_can_upgrade_secondary_to_primary(): void
    {
        $domainName = new DomainName('example', 'com');
        $account    = $this->createAccount(self::ACCOUNT_ID1);

        $webhostingDomainName = WebhostingDomainName::registerSecondary($account, $domainName);
        $webhostingDomainName->markPrimary();

        self::assertEquals($domainName, $webhostingDomainName->domainName());
        self::assertTrue($webhostingDomainName->isPrimary());
    }

    /** @test */
    public function it_can_change_name(): void
    {
        $webhostingDomainName = WebhostingDomainName::registerSecondary(
            $this->createAccount(self::ACCOUNT_ID1),
            new DomainName('example', 'com')
        );

        $webhostingDomainName->changeName($name = new DomainName('example', 'com'));

        self::assertEquals($name, $webhostingDomainName->domainName());
    }

    /** @test */
    public function it_can_transfer_secondary_domainName(): void
    {
        $account2             = $this->createAccount(self::ACCOUNT_ID2);
        $webhostingDomainName = WebhostingDomainName::registerSecondary(
            $this->createAccount(self::ACCOUNT_ID1),
            new DomainName('example', 'com')
        );

        $webhostingDomainName->transferToAccount($account2);

        self::assertEquals($account2, $webhostingDomainName->account());
    }

    /** @test */
    public function it_cannot_transfer_primary_domainName(): void
    {
        $account2             = $this->createAccount(self::ACCOUNT_ID2);
        $account1             = $this->createAccount(self::ACCOUNT_ID1);
        $webhostingDomainName = WebhostingDomainName::registerPrimary($account1, new DomainName('example', 'com'));

        $this->expectException(CannotTransferPrimaryDomainName::class);
        $this->expectExceptionMessage(
            CannotTransferPrimaryDomainName::of($webhostingDomainName->id(), $account1->id(), $account2->id())->getMessage()
        );

        $webhostingDomainName->transferToAccount($account2);
    }

    private function createAccount(string $id): WebhostingAccount
    {
        $account = $this->createMock(WebhostingAccount::class);
        $account
            ->expects(self::any())
            ->method('id')
            ->willReturn(WebhostingAccountId::fromString($id));

        return $account;
    }
}
