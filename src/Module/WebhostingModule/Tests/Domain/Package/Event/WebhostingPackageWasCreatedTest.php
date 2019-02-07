<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Tests\Domain\Package\Event;

use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\Event\WebhostingPackageWasCreated;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageId;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingPackageWasCreatedTest extends TestCase
{
    private const WEBHOSTING_PACKAGE_ID = 'b3e3846a-97c6-11e7-bf67-acbc32b58315';

    /** @test */
    public function its_constructable(): void
    {
        $event = new WebhostingPackageWasCreated(
            $id = WebhostingPackageId::fromString(self::WEBHOSTING_PACKAGE_ID),
            $capabilities = new Capabilities(new StorageSpaceQuota('5G'), new MonthlyTrafficQuota(50))
        );

        self::assertTrue($id->equals($event->id()));
        self::assertEquals($capabilities, $event->capabilities());
    }
}
