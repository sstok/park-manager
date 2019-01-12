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

namespace ParkManager\Module\WebhostingModule\Tests\Domain\Account;

use DateTimeImmutable;
use ParkManager\Module\CoreModule\Domain\Shared\OwnerId;
use ParkManager\Module\CoreModule\Test\Domain\EventsRecordingEntityAssertionTrait;
use ParkManager\Module\WebhostingModule\Domain\Account\Event\WebhostingAccountCapabilitiesWasChanged;
use ParkManager\Module\WebhostingModule\Domain\Account\Event\WebhostingAccountOwnerWasSwitched;
use ParkManager\Module\WebhostingModule\Domain\Account\Event\WebhostingAccountPackageAssignmentWasChanged;
use ParkManager\Module\WebhostingModule\Domain\Account\Event\WebhostingAccountWasMarkedForRemoval;
use ParkManager\Module\WebhostingModule\Domain\Account\Event\WebhostingAccountWasRegistered;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackage;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageId;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingAccountTest extends TestCase
{
    use EventsRecordingEntityAssertionTrait;

    private const ACCOUNT_ID = '374dd50e-9b9f-11e7-9730-acbc32b58315';

    private const OWNER_ID1 = '2a9cd25c-97ca-11e7-9683-acbc32b58315';
    private const OWNER_ID2 = 'ce18c388-9ba2-11e7-b15f-acbc32b58315';

    private const PACKAGE_ID_1 = '654665ea-9869-11e7-9563-acbc32b58315';
    private const PACKAGE_ID_2 = 'f5788aae-9aed-11e7-a3c9-acbc32b58315';

    /** @test */
    public function it_registers_an_webhosting_account()
    {
        $id           = WebhostingAccountId::create();
        $capabilities = new Capabilities();
        $package      = $this->createWebhostingPackage($capabilities);

        $account = WebhostingAccount::register($id, $owner = OwnerId::fromString(self::OWNER_ID1), $package);

        self::assertEquals($id, $account->id());
        self::assertEquals($owner, $account->owner());
        self::assertSame($package, $account->package());
        self::assertSame($capabilities, $account->capabilities());
        self::assertDomainEvents($account, [new WebhostingAccountWasRegistered($id, $owner)]);
    }

    /** @test */
    public function it_registers_an_webhosting_account_with_custom_capabilities()
    {
        $id           = WebhostingAccountId::create();
        $capabilities = new Capabilities();

        $account = WebhostingAccount::registerWithCustomCapabilities($id, $owner = OwnerId::fromString(self::OWNER_ID1), $capabilities);

        self::assertEquals($id, $account->id());
        self::assertEquals($owner, $account->owner());
        self::assertSame($capabilities, $account->capabilities());
        self::assertNull($account->package());
        self::assertDomainEvents($account, [new WebhostingAccountWasRegistered($id, $owner)]);
    }

    /** @test */
    public function it_allows_changing_package_assignment()
    {
        $id2           = WebhostingAccountId::create();
        $capabilities1 = new Capabilities();
        $capabilities2 = new Capabilities(new MonthlyTrafficQuota(50));
        $package1      = $this->createWebhostingPackage($capabilities1);
        $package2      = $this->createWebhostingPackage($capabilities2, self::PACKAGE_ID_2);
        $account1      = WebhostingAccount::register(WebhostingAccountId::create(), OwnerId::fromString(self::OWNER_ID1), $package1);
        $account2      = WebhostingAccount::register($id2, OwnerId::fromString(self::OWNER_ID1), $package1);
        self::resetDomainEvents($account1, $account2);

        $account1->assignPackage($package1);
        $account2->assignPackage($package2);

        self::assertSame($package1, $account1->package(), 'Package should not change');
        self::assertSame($package1->capabilities(), $account1->capabilities(), 'Capabilities should not change');
        self::assertNoDomainEvents($account1);

        self::assertSame($package2, $account2->package());
        self::assertSame($package1->capabilities(), $account2->capabilities());
        self::assertDomainEvents($account2, [new WebhostingAccountPackageAssignmentWasChanged($id2, $package2)]);
    }

    /** @test */
    public function it_allows_changing_package_assignment_with_capabilities()
    {
        $id2           = WebhostingAccountId::create();
        $capabilities1 = new Capabilities();
        $capabilities2 = new Capabilities(new MonthlyTrafficQuota(50));
        $package1      = $this->createWebhostingPackage($capabilities1);
        $package2      = $this->createWebhostingPackage($capabilities2, self::PACKAGE_ID_2);
        $account1      = WebhostingAccount::register(WebhostingAccountId::create(), OwnerId::fromString(self::OWNER_ID1), $package1);
        $account2      = WebhostingAccount::register($id2, OwnerId::fromString(self::OWNER_ID1), $package1);
        self::resetDomainEvents($account1, $account2);

        $account1->assignPackageWithCapabilities($package1);
        $account2->assignPackageWithCapabilities($package2);

        self::assertSame($package1, $account1->package(), 'Package should not change');
        self::assertSame($package1->capabilities(), $account1->capabilities(), 'Capabilities should not change');
        self::assertNoDomainEvents($account1);

        self::assertSame($package2, $account2->package());
        self::assertSame($package2->capabilities(), $account2->capabilities());
        self::assertDomainEvents(
            $account2,
            [WebhostingAccountPackageAssignmentWasChanged::withCapabilities($id2, $package2)]
        );
    }

    /** @test */
    public function it_updates_account_when_assigning_package_capabilities_are_different()
    {
        $id      = WebhostingAccountId::create();
        $package = $this->createWebhostingPackage(new Capabilities());
        $account = WebhostingAccount::register($id, OwnerId::fromString(self::OWNER_ID1), $package);
        self::resetDomainEvents($account);

        $package->changeCapabilities($newCapabilities = new Capabilities(new MonthlyTrafficQuota(50)));
        $account->assignPackageWithCapabilities($package);

        self::assertSame($package, $account->package());
        self::assertSame($package->capabilities(), $account->capabilities());
        self::assertDomainEvents(
            $account,
            [WebhostingAccountPackageAssignmentWasChanged::withCapabilities($id, $package)]
        );
    }

    /** @test */
    public function it_allows_assigning_custom_specification()
    {
        $id      = WebhostingAccountId::create();
        $package = $this->createWebhostingPackage(new Capabilities());
        $account = WebhostingAccount::register($id, OwnerId::fromString(self::OWNER_ID1), $package);
        self::resetDomainEvents($account);

        $account->assignCustomCapabilities($newCapabilities = new Capabilities(new MonthlyTrafficQuota(50)));

        self::assertNull($account->package());
        self::assertSame($newCapabilities, $account->capabilities());
        self::assertDomainEvents($account, [new WebhostingAccountCapabilitiesWasChanged($id, $newCapabilities)]);
    }

    /** @test */
    public function it_allows_changing_custom_specification()
    {
        $id      = WebhostingAccountId::create();
        $account = WebhostingAccount::registerWithCustomCapabilities($id, OwnerId::fromString(self::OWNER_ID1), new Capabilities());
        self::resetDomainEvents($account);

        $account->assignCustomCapabilities($newCapabilities = new Capabilities(new MonthlyTrafficQuota(50)));

        self::assertNull($account->package());
        self::assertSame($newCapabilities, $account->capabilities());
        self::assertDomainEvents($account, [new WebhostingAccountCapabilitiesWasChanged($id, $newCapabilities)]);
    }

    /** @test */
    public function it_does_not_update_account_capabilities_when_assigning_capabilities_are_same()
    {
        $id           = WebhostingAccountId::create();
        $capabilities = new Capabilities();
        $account      = WebhostingAccount::registerWithCustomCapabilities($id, OwnerId::fromString(self::OWNER_ID1), $capabilities);
        self::resetDomainEvents($account);

        $account->assignCustomCapabilities($capabilities);

        self::assertNull($account->package());
        self::assertSame($capabilities, $account->capabilities());
        self::assertNoDomainEvents($account);
    }

    /** @test */
    public function it_supports_switching_the_account_owner()
    {
        $account1 = WebhostingAccount::register(
            WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPackage(new Capabilities())
        );
        $account2 = WebhostingAccount::register(
            $id2 = WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPackage(new Capabilities())
        );
        self::resetDomainEvents($account1, $account2);

        $account1->switchOwner($owner1 = OwnerId::fromString(self::OWNER_ID1));
        $account2->switchOwner($owner2 = OwnerId::fromString(self::OWNER_ID2));

        self::assertEquals($owner1, $account1->owner());
        self::assertNoDomainEvents($account1);

        self::assertEquals($owner2, $account2->owner());
        self::assertDomainEvents($account2, [new WebhostingAccountOwnerWasSwitched($id2, $owner1, $owner2)]);
    }

    /** @test */
    public function it_allows_being_marked_for_removal()
    {
        $account1 = WebhostingAccount::register(
            WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPackage(new Capabilities())
        );
        $account2 = WebhostingAccount::register(
            $id2 = WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPackage(new Capabilities())
        );
        self::resetDomainEvents($account1, $account2);

        $account2->markForRemoval();
        $account2->markForRemoval();

        self::assertFalse($account1->isMarkedForRemoval());
        self::assertTrue($account2->isMarkedForRemoval());
        self::assertDomainEvents($account2, [new WebhostingAccountWasMarkedForRemoval($id2)]);
    }

    /** @test */
    public function it_can_expire()
    {
        $account1 = WebhostingAccount::register(
            WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPackage(new Capabilities())
        );
        $account2 = WebhostingAccount::register(
            $id2 = WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPackage(new Capabilities())
        );
        self::resetDomainEvents($account1, $account2);

        $account2->setExpirationDate($date = new DateTimeImmutable('now +6 days'));

        self::assertFalse($account1->isExpired());
        self::assertFalse($account1->isExpired($date->modify('+2 days')));

        self::assertFalse($account2->isExpired($date->modify('-10 days')));
        self::assertTrue($account2->isExpired($date));
        self::assertTrue($account2->isExpired($date->modify('+2 days')));

        $account1->removeExpirationDate();
        $account2->removeExpirationDate();

        self::assertFalse($account1->isExpired());
        self::assertFalse($account2->isExpired($date));
        self::assertFalse($account2->isExpired($date->modify('+2 days')));
    }

    private function createWebhostingPackage(Capabilities $capabilities, string $id = self::PACKAGE_ID_1): WebhostingPackage
    {
        return WebhostingPackage::create(WebhostingPackageId::fromString($id), $capabilities);
    }
}
