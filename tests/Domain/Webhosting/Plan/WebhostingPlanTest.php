<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Plan;

use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\StorageSpaceQuota;
use ParkManager\Domain\Webhosting\Plan\Constraints;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlan;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlanId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingPlanTest extends TestCase
{
    private const ID1 = '654665ea-9869-11e7-9563-acbc32b58315';

    /** @test */
    public function it_registers_a_webhosting_plan(): void
    {
        $plan = new WebhostingPlan(
            $id = WebhostingPlanId::fromString(self::ID1),
            $constraints = new Constraints()
        );

        static::assertEquals($constraints, $plan->getConstraints());
        static::assertEquals([], $plan->getMetadata());
    }

    /** @test */
    public function it_allows_changing_constraints(): void
    {
        $plan = $this->createPlan();
        $plan->changeConstraints(
            $constraints = new Constraints(new StorageSpaceQuota('5G'), new MonthlyTrafficQuota(50))
        );

        static::assertEquals($constraints, $plan->getConstraints());
    }

    /** @test */
    public function it_supports_setting_metadata(): void
    {
        $plan = $this->createPlan();
        $plan->withMetadata(['label' => 'Gold']);

        static::assertEquals(['label' => 'Gold'], $plan->getMetadata());
    }

    private function createPlan(): WebhostingPlan
    {
        return new WebhostingPlan(WebhostingPlanId::fromString(self::ID1), new Constraints());
    }
}
