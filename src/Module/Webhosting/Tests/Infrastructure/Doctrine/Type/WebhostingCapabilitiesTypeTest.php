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

namespace ParkManager\Module\Webhosting\Tests\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use ParkManager\Module\Webhosting\Infrastructure\Doctrine\Type\WebhostingCapabilitiesType;
use ParkManager\Module\Webhosting\Model\Package\Capabilities;
use ParkManager\Module\Webhosting\Model\Package\CapabilitiesFactory;
use ParkManager\Module\Webhosting\Tests\Fixtures\Capability\MonthlyTrafficQuota;
use ParkManager\Module\Webhosting\Tests\Fixtures\Capability\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

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
        if (!Type::hasType('webhosting_capabilities')) {
            Type::addType('webhosting_capabilities', WebhostingCapabilitiesType::class);
        }
    }

    /** @test */
    public function it_converts_to_database_value()
    {
        $type = $this->getDbalType();
        $platform = $this->createPlatform();

        self::assertNull($type->convertToDatabaseValue(null, $platform));
        self::assertJsonStringEqualsJsonString('[]', $type->convertToDatabaseValue(new Capabilities(), $platform));
        self::assertJsonStringEqualsJsonString(
            '{"'.MonthlyTrafficQuota::id().'":{"limit":50}}',
            $type->convertToDatabaseValue(new Capabilities(new MonthlyTrafficQuota(50)), $platform)
        );
    }

    /** @test */
    public function it_converts_from_database_value_to_php_value()
    {
        $type = $this->getDbalType();
        $platform = $this->createPlatform();

        self::assertEquals(new Capabilities(), $type->convertToPHPValue(null, $platform));
        self::assertEquals(new Capabilities(), $type->convertToPHPValue('[]', $platform));
        self::assertEquals(new Capabilities(), $type->convertToPHPValue('{}', $platform));
        self::assertEquals(
            new Capabilities(new MonthlyTrafficQuota(50)),
            $type->convertToPHPValue('{"'.MonthlyTrafficQuota::id().'":{"limit":50}}', $platform)
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
        $factoryProphecy = $this->prophesize(CapabilitiesFactory::class);
        $factoryProphecy->createById(MonthlyTrafficQuota::id(), Argument::any())->will(function ($args) {
            return MonthlyTrafficQuota::reconstituteFromArray($args[1]);
        });
        $factoryProphecy->createById(StorageSpaceQuota::id(), Argument::any())->will(function ($args) {
            return StorageSpaceQuota::reconstituteFromArray($args[1]);
        });

        return $factoryProphecy->reveal();
    }
}
