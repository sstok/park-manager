<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Tests\Infrastructure\Doctrine\Package;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Infrastructure\Doctrine\Package\WebhostingCapabilitiesType;
use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\CapabilitiesFactory;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingCapabilitiesTypeTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setUpType(): void
    {
        if (! Type::hasType('webhosting_capabilities')) {
            Type::addType('webhosting_capabilities', WebhostingCapabilitiesType::class);
        }
    }

    /** @test */
    public function it_converts_to_database_value(): void
    {
        $type     = $this->getDbalType();
        $platform = $this->createPlatform();

        self::assertNull($type->convertToDatabaseValue(null, $platform));
        self::assertJsonStringEqualsJsonString('[]', $type->convertToDatabaseValue(new Capabilities(), $platform));
        self::assertJsonStringEqualsJsonString(
            '{"' . MonthlyTrafficQuota::id() . '":{"limit":50}}',
            $type->convertToDatabaseValue(new Capabilities(new MonthlyTrafficQuota(50)), $platform)
        );
    }

    /** @test */
    public function it_converts_from_database_value_to_php_value(): void
    {
        $type     = $this->getDbalType();
        $platform = $this->createPlatform();

        self::assertEquals(new Capabilities(), $type->convertToPHPValue(null, $platform));
        self::assertEquals(new Capabilities(), $type->convertToPHPValue('[]', $platform));
        self::assertEquals(new Capabilities(), $type->convertToPHPValue('{}', $platform));
        self::assertEquals(
            new Capabilities(new MonthlyTrafficQuota(50)),
            $type->convertToPHPValue('{"' . MonthlyTrafficQuota::id() . '":{"limit":50}}', $platform)
        );
    }

    private function createPlatform()
    {
        return $this->createMock(AbstractPlatform::class);
    }

    private function getDbalType(): WebhostingCapabilitiesType
    {
        /** @var WebhostingCapabilitiesType $type */
        $type = Type::getType('webhosting_capabilities');
        $type->setCapabilitiesFactory($this->createCapabilitiesFactory());

        return $type;
    }

    private function createCapabilitiesFactory(): CapabilitiesFactory
    {
        return new CapabilitiesFactory([
            MonthlyTrafficQuota::id() => MonthlyTrafficQuota::class,
            StorageSpaceQuota::id() => StorageSpaceQuota::class,
        ]);
    }
}
