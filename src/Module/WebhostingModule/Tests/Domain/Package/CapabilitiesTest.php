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

namespace ParkManager\Module\WebhostingModule\Tests\Domain\Package;

use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\Exception\CapabilityNotInSet;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;
use function get_class;
use function iterator_to_array;

/**
 * @internal
 */
final class CapabilitiesTest extends TestCase
{
    /** @test */
    public function its_constructable()
    {
        $capability   = new StorageSpaceQuota('9B');
        $capability2  = new MonthlyTrafficQuota(50);
        $capabilities = new Capabilities($capability, $capability);

        self::assertCapabilitiesEquals([$capability], $capabilities);
        self::assertTrue($capabilities->has(get_class($capability)));
        self::assertFalse($capabilities->has(get_class($capability2)));
        self::assertEquals($capability, $capabilities->get(StorageSpaceQuota::class));
    }

    /** @test */
    public function it_throws_when_getting_unset_capability()
    {
        $capability   = new StorageSpaceQuota('9B');
        $capabilities = new Capabilities($capability);

        $this->expectException(CapabilityNotInSet::class);
        $this->expectExceptionMessage(CapabilityNotInSet::withName(MonthlyTrafficQuota::class)->getMessage());

        $capabilities->get(MonthlyTrafficQuota::class);
    }

    /** @test */
    public function it_allows_adding_and_returns_new_set()
    {
        $capability  = new StorageSpaceQuota('9B');
        $capability2 = new MonthlyTrafficQuota(50);

        $capabilities    = new Capabilities($capability);
        $capabilitiesNew = $capabilities->add($capability2);

        self::assertNotSame($capabilities, $capabilitiesNew);
        self::assertCapabilitiesEquals([$capability], $capabilities);
        self::assertCapabilitiesEquals([$capability, $capability2], $capabilitiesNew);
    }

    /** @test */
    public function it_allows_removing_and_returns_new_set()
    {
        $capability  = new StorageSpaceQuota('9B');
        $capability2 = new MonthlyTrafficQuota(50);

        $capabilities    = new Capabilities($capability, $capability2);
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
