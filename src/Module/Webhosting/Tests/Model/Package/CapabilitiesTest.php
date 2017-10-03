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

namespace ParkManager\Module\Webhosting\Tests\Model\Package;

use ParkManager\Module\Webhosting\Model\Package\Capabilities;
use ParkManager\Module\Webhosting\Tests\Fixtures\Capability\MonthlyTrafficQuota;
use ParkManager\Module\Webhosting\Tests\Fixtures\Capability\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CapabilitiesTest extends TestCase
{
    private const ID1 = '90ecb7de-9635-11e7-82db-acbc32b58315';
    private const ID2 = '52c7d220-9637-11e7-8140-acbc32b58315';

    /** @test */
    public function its_constructable()
    {
        $capability = new StorageSpaceQuota('9B');
        $capability2 = new MonthlyTrafficQuota(50);
        $capabilities = new Capabilities($capability, $capability);

        self::assertCapabilitiesEquals([$capability], $capabilities);
        self::assertTrue($capabilities->has(get_class($capability)));
        self::assertFalse($capabilities->has(get_class($capability2)));
    }

    /** @test */
    public function it_allows_adding_and_returns_new_set()
    {
        $capability = new StorageSpaceQuota('9B');
        $capability2 = new MonthlyTrafficQuota(50);

        $capabilities = new Capabilities($capability);
        $capabilitiesNew = $capabilities->add($capability2);

        self::assertNotSame($capabilities, $capabilitiesNew);
        self::assertCapabilitiesEquals([$capability], $capabilities);
        self::assertCapabilitiesEquals([$capability, $capability2], $capabilitiesNew);
    }

    /** @test */
    public function it_allows_removing_and_returns_new_set()
    {
        $capability = new StorageSpaceQuota('9B');
        $capability2 = new MonthlyTrafficQuota(50);

        $capabilities = new Capabilities($capability, $capability2);
        $capabilitiesNew = $capabilities->remove($capability);

        self::assertNotSame($capabilities, $capabilitiesNew);
        self::assertCapabilitiesEquals([$capability, $capability2], $capabilities);
        self::assertCapabilitiesEquals([$capability2], $capabilitiesNew);
    }

    private static function assertCapabilitiesEquals(array $capabilities, Capabilities $capabilitiesSet): void
    {
        $processedCapabilities = [];
        foreach ($capabilities as $capability) {
            $processedCapabilities[get_class($capability)] = $capability;
        }

        self::assertEquals($processedCapabilities, iterator_to_array($capabilitiesSet));
    }
}
