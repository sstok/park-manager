<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Tests\Domain\Package;

use ParkManager\Module\CoreModule\Test\Domain\EventsRecordingEntityAssertionTrait;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\Event\WebhostingPackageCapabilitiesWasChanged;
use ParkManager\Module\WebhostingModule\Domain\Package\Event\WebhostingPackageWasCreated;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackage;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageId;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingPackageTest extends TestCase
{
    use EventsRecordingEntityAssertionTrait;

    private const ID1 = '654665ea-9869-11e7-9563-acbc32b58315';

    /** @test */
    public function it_registers_a_webhosting_package(): void
    {
        $package = WebhostingPackage::create(
            $id = WebhostingPackageId::fromString(self::ID1),
            $capabilities = new Capabilities()
        );

        self::assertEquals($capabilities, $package->capabilities());
        self::assertDomainEvents($package, [new WebhostingPackageWasCreated($id, $capabilities)]);
        self::assertEquals([], $package->metadata());
    }

    /** @test */
    public function it_allows_changing_capabilities(): void
    {
        $package = $this->createPackage();
        $package->changeCapabilities(
            $capabilities = new Capabilities(new StorageSpaceQuota('5G'), new MonthlyTrafficQuota(50))
        );
        $id = $package->id();

        $package2 = $this->createPackage();
        $package2->changeCapabilities($package2->capabilities());

        self::assertEquals($capabilities, $package->capabilities());
        self::assertDomainEvents(
            $package,
            [new WebhostingPackageCapabilitiesWasChanged($id, $capabilities)]
        );
        self::assertNoDomainEvents($package2);
    }

    /** @test */
    public function it_supports_setting_metadata(): void
    {
        $package = $this->createPackage();
        $package->withMetadata(['label' => 'Gold']);

        self::assertNoDomainEvents($package);
        self::assertEquals(['label' => 'Gold'], $package->metadata());
    }

    private function createPackage(): WebhostingPackage
    {
        $package = WebhostingPackage::create(WebhostingPackageId::fromString(self::ID1), new Capabilities());
        static::resetDomainEvents($package);

        return $package;
    }
}
