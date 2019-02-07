<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Tests\Domain\Account\Command;

use ParkManager\Module\CoreModule\Domain\Shared\OwnerId;
use ParkManager\Module\WebhostingModule\Application\Account\RegisterWebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\DomainName;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageId;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RegisterWebhostingAccountTest extends TestCase
{
    private const ACCOUNT_ID = 'b288e23c-97c5-11e7-b51a-acbc32b58315';
    private const OWNER_ID   = '2a9cd25c-97ca-11e7-9683-acbc32b58315';
    private const PACKAGE_ID = '654665ea-9869-11e7-9563-acbc32b58315';

    /** @test */
    public function its_constructable_with_package(): void
    {
        $command = RegisterWebhostingAccount::withPackage(
            self::ACCOUNT_ID,
            $domainName = new DomainName('example', 'com'),
            self::OWNER_ID,
            self::PACKAGE_ID
        );

        self::assertEquals(WebhostingAccountId::fromString(self::ACCOUNT_ID), $command->id());
        self::assertEquals(OwnerId::fromString(self::OWNER_ID), $command->owner());
        self::assertEquals(WebhostingPackageId::fromString(self::PACKAGE_ID), $command->package());
        self::assertEquals($domainName, $command->domainName());
        self::assertNull($command->customCapabilities());
    }

    /** @test */
    public function its_constructable_with_custom_capabilities(): void
    {
        $command = RegisterWebhostingAccount::withCustomCapabilities(
            self::ACCOUNT_ID,
            $domainName = new DomainName('example', 'com'),
            self::OWNER_ID,
            $capabilities = new Capabilities(new MonthlyTrafficQuota(50))
        );

        self::assertEquals(WebhostingAccountId::fromString(self::ACCOUNT_ID), $command->id());
        self::assertEquals(OwnerId::fromString(self::OWNER_ID), $command->owner());
        self::assertEquals($capabilities, $command->customCapabilities());
        self::assertEquals($domainName, $command->domainName());
        self::assertNull($command->package());
    }
}
