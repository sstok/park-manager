<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Constraint;

use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\StorageSpaceQuota;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Exception\ConstraintNotInSet;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ConstraintsTest extends TestCase
{
    /** @test */
    public function its_constructable(): void
    {
        $constraint = new StorageSpaceQuota('9B');
        $constraints = new Constraints($constraint, $constraint);

        self::assertConstraintsEquals([$constraint], $constraints);
        static::assertTrue($constraints->has('StorageSpaceQuota'));
        static::assertFalse($constraints->has('MonthlyTrafficQuota'));
        static::assertEquals($constraint, $constraints->get('StorageSpaceQuota'));
    }

    /** @test */
    public function it_throws_when_getting_unset_constraint(): void
    {
        $constraint = new StorageSpaceQuota('9B');
        $constraints = new Constraints($constraint);

        $this->expectException(ConstraintNotInSet::class);
        $this->expectExceptionMessage(ConstraintNotInSet::withName(MonthlyTrafficQuota::class)->getMessage());

        $constraints->get(MonthlyTrafficQuota::class);
    }

    /** @test */
    public function it_allows_adding_and_returns_new_set(): void
    {
        $constraint = new StorageSpaceQuota('9B');
        $constraint2 = new MonthlyTrafficQuota(50);

        $constraints = new Constraints($constraint);
        $constraintsNew = $constraints->add($constraint2);

        static::assertNotSame($constraints, $constraintsNew);
        self::assertConstraintsEquals([$constraint], $constraints);
        self::assertConstraintsEquals([$constraint, $constraint2], $constraintsNew);
    }

    /** @test */
    public function it_allows_removing_and_returns_new_set(): void
    {
        $constraint = new StorageSpaceQuota('9B');
        $constraint2 = new MonthlyTrafficQuota(50);

        $constraints = new Constraints($constraint, $constraint2);
        $constraintsNew = $constraints->remove($constraint);

        static::assertNotSame($constraints, $constraintsNew);
        self::assertConstraintsEquals([$constraint, $constraint2], $constraints);
        self::assertConstraintsEquals([$constraint2], $constraintsNew);
    }

    private static function assertConstraintsEquals(array $constraints, Constraints $constraintsSet): void
    {
        $processedConstraints = [];

        foreach ($constraints as $constraint) {
            $processedConstraints[Constraints::getConstraintName($constraint)] = $constraint;
        }

        static::assertEquals($processedConstraints, \iterator_to_array($constraintsSet));
    }
}
