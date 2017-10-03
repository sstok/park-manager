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

namespace ParkManager\Module\Webhosting\Tests\Model\Package\Event;

use ParkManager\Component\Model\Test\DomainMessageAssertion;
use ParkManager\Module\Webhosting\Model\Package\Capabilities;
use ParkManager\Module\Webhosting\Model\Package\Event\WebhostingPackageCapabilitiesWasChanged;
use ParkManager\Module\Webhosting\Model\Package\WebhostingPackageId;
use ParkManager\Module\Webhosting\Tests\Fixtures\Capability\MonthlyTrafficQuota;
use ParkManager\Module\Webhosting\Tests\Fixtures\Capability\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingPackageCapabilitiesWasChangedTest extends TestCase
{
    private const PACKAGE_ID = 'b3e3846a-97c6-11e7-bf67-acbc32b58315';

    /** @test */
    public function its_constructable()
    {
        $event = WebhostingPackageCapabilitiesWasChanged::withData(
            $id = WebhostingPackageId::fromString(self::PACKAGE_ID),
            $capabilities = new Capabilities(new StorageSpaceQuota('5G'), new MonthlyTrafficQuota(50))
        );

        self::assertTrue($id->equals($event->id()));
        self::assertEquals($capabilities, $event->capabilities());

        DomainMessageAssertion::assertGettersEqualAfterEncoding($event);
    }
}
