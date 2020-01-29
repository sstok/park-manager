<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\ConstraintSetId;
use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSet;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SharedConstraintSetTest extends TestCase
{
    private const ID1 = '654665ea-9869-11e7-9563-acbc32b58315';

    /** @test */
    public function it_registers_a_webhosting_constraint_set(): void
    {
        $constraintSet = new SharedConstraintSet(
            $id = ConstraintSetId::fromString(self::ID1),
            $constraints = new Constraints()
        );

        static::assertEquals($constraints, $constraintSet->getConstraints());
        static::assertEquals([], $constraintSet->getMetadata());
    }

    /** @test */
    public function it_allows_changing_constraints(): void
    {
        $constraintSet = $this->createConstraintSet();
        $constraintSet->changeConstraints(
            $constraints = new Constraints(new StorageSpaceQuota('5G'), new MonthlyTrafficQuota(50))
        );

        static::assertEquals($constraints, $constraintSet->getConstraints());
    }

    /** @test */
    public function it_supports_setting_metadata(): void
    {
        $constraintSet = $this->createConstraintSet();
        $constraintSet->withMetadata(['label' => 'Gold']);

        static::assertEquals(['label' => 'Gold'], $constraintSet->getMetadata());
    }

    private function createConstraintSet(): SharedConstraintSet
    {
        return new SharedConstraintSet(ConstraintSetId::fromString(self::ID1), new Constraints());
    }
}
