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

namespace ParkManager\Module\WebhostingModule\Tests\Domain\Account\Event;

use ParkManager\Module\WebhostingModule\Domain\Account\Event\WebhostingAccountPackageAssignmentWasChanged;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackage;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageId;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingAccountPackageAssignmentWasChangedTest extends TestCase
{
    private const WEBHOSTING_PACKAGE_ID = 'b3e3846a-97c6-11e7-bf67-acbc32b58315';
    private const ACCOUNT_ID = 'b288e23c-97c5-11e7-b51a-acbc32b58315';

    /** @test */
    public function its_constructable()
    {
        $event = new WebhostingAccountPackageAssignmentWasChanged(
            $id = $this->createAccountId(),
            $package = $this->createWebhostingPackage()
        );

        self::assertTrue($id->equals($event->id()));
        self::assertEquals($package->id(), $event->package());
        self::assertNull($event->capabilities());
    }

    /** @test */
    public function its_constructable_with_capabilities_provided()
    {
        $event = WebhostingAccountPackageAssignmentWasChanged::withCapabilities(
            $id = $this->createAccountId(),
            $package = $this->createWebhostingPackage()
        );

        self::assertTrue($id->equals($event->id()));
        self::assertEquals($package->id(), $event->package());
        self::assertEquals($package->capabilities(), $event->capabilities());
    }

    private function createWebhostingPackage(): WebhostingPackage
    {
        return WebhostingPackage::create(
            WebhostingPackageId::fromString(self::WEBHOSTING_PACKAGE_ID),
            new Capabilities(new StorageSpaceQuota('5G'), new MonthlyTrafficQuota(50))
        );
    }

    private function createAccountId(): WebhostingAccountId
    {
        return WebhostingAccountId::fromString(self::ACCOUNT_ID);
    }
}
