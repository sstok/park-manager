<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Doctrine\Plan;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use ParkManager\Bundle\WebhostingBundle\Doctrine\Plan\WebhostingPlanConstraintsType;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintsFactory;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuota;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingPlanConstraintsTypeTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setUpType(): void
    {
        if (! Type::hasType('webhosting_plan_constraints')) {
            Type::addType('webhosting_plan_constraints', WebhostingPlanConstraintsType::class);
        }
    }

    /** @test */
    public function it_converts_to_database_value(): void
    {
        $type     = $this->getDbalType();
        $platform = $this->createPlatform();

        self::assertNull($type->convertToDatabaseValue(null, $platform));
        self::assertJsonStringEqualsJsonString('[]', $type->convertToDatabaseValue(new Constraints(), $platform));
        self::assertJsonStringEqualsJsonString(
            '{"' . MonthlyTrafficQuota::id() . '":{"limit":50}}',
            $type->convertToDatabaseValue(new Constraints(new MonthlyTrafficQuota(50)), $platform)
        );
    }

    /** @test */
    public function it_converts_from_database_value_to_php_value(): void
    {
        $type     = $this->getDbalType();
        $platform = $this->createPlatform();

        self::assertEquals(new Constraints(), $type->convertToPHPValue(null, $platform));
        self::assertEquals(new Constraints(), $type->convertToPHPValue('[]', $platform));
        self::assertEquals(new Constraints(), $type->convertToPHPValue('{}', $platform));
        self::assertEquals(
            new Constraints(new MonthlyTrafficQuota(50)),
            $type->convertToPHPValue('{"' . MonthlyTrafficQuota::id() . '":{"limit":50}}', $platform)
        );
    }

    private function createPlatform()
    {
        return $this->createMock(AbstractPlatform::class);
    }

    private function getDbalType(): WebhostingPlanConstraintsType
    {
        /** @var WebhostingPlanConstraintsType $type */
        $type = Type::getType('webhosting_plan_constraints');
        $type->setConstraintsFactory($this->createConstraintsFactory());

        return $type;
    }

    private function createConstraintsFactory(): ConstraintsFactory
    {
        return new ConstraintsFactory([
            MonthlyTrafficQuota::id() => MonthlyTrafficQuota::class,
            StorageSpaceQuota::id() => StorageSpaceQuota::class,
        ]);
    }
}
