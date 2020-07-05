<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PlanTest extends TestCase
{
    private const ID1 = '654665ea-9869-11e7-9563-acbc32b58315';

    /** @test */
    public function it_registers_a_webhosting_plan(): void
    {
        $plan = new Plan(
            $id = PlanId::fromString(self::ID1),
            $constraints = new Constraints()
        );

        self::assertEquals($constraints, $plan->getConstraints());
        self::assertEquals([], $plan->getMetadata());
    }

    /** @test */
    public function it_allows_changing_constraints(): void
    {
        $plan = $this->create();
        $plan->changeConstraints(
            $constraints = (new Constraints())->setMonthlyTraffic(50)
        );

        self::assertEquals($constraints, $plan->getConstraints());
    }

    /** @test */
    public function it_supports_setting_metadata(): void
    {
        $plan = $this->create();
        $plan->withMetadata(['label' => 'Gold']);

        self::assertEquals(['label' => 'Gold'], $plan->getMetadata());
    }

    private function create(): Plan
    {
        return new Plan(PlanId::fromString(self::ID1), new Constraints());
    }
}
