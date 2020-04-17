<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Infrastructure\Doctrine\Type\WebhostingConstraintsType;
use ParkManager\Infrastructure\Webhosting\Constraint\ConstraintsFactory;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingConstraintsTypeTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setUpType(): void
    {
        if (! Type::hasType('webhosting_constraintSet_constraints')) {
            Type::addType('webhosting_constraintSet_constraints', WebhostingConstraintsType::class);
        }
    }

    /** @test */
    public function it_converts_to_database_value(): void
    {
        $type = $this->getDbalType();
        $platform = $this->createPlatform();

        self::assertNull($type->convertToDatabaseValue(null, $platform));
        self::assertJsonStringEqualsJsonString('[]', $type->convertToDatabaseValue(new Constraints(), $platform));
        self::assertJsonStringEqualsJsonString(
            '{"MonthlyTrafficQuota":{"limit":50}}',
            $type->convertToDatabaseValue(new Constraints(new MonthlyTrafficQuota(50)), $platform)
        );
    }

    /** @test */
    public function it_converts_from_database_value_to_php_value(): void
    {
        $type = $this->getDbalType();
        $platform = $this->createPlatform();

        self::assertEquals(new Constraints(), $type->convertToPHPValue(null, $platform));
        self::assertEquals(new Constraints(), $type->convertToPHPValue('[]', $platform));
        self::assertEquals(new Constraints(), $type->convertToPHPValue('{}', $platform));
        self::assertEquals(
            new Constraints(new MonthlyTrafficQuota(50)),
            $type->convertToPHPValue('{"MonthlyTrafficQuota":{"limit":50}}', $platform)
        );
    }

    private function createPlatform()
    {
        return $this->createMock(AbstractPlatform::class);
    }

    private function getDbalType(): WebhostingConstraintsType
    {
        $type = Type::getType('webhosting_constraintSet_constraints');
        \assert($type instanceof WebhostingConstraintsType);
        $type->setConstraintsFactory($this->createConstraintsFactory());

        return $type;
    }

    private function createConstraintsFactory(): ConstraintsFactory
    {
        return new ConstraintsFactory([
            'MonthlyTrafficQuota' => MonthlyTrafficQuota::class,
            'StorageSpaceQuota' => StorageSpaceQuota::class,
        ]);
    }
}
