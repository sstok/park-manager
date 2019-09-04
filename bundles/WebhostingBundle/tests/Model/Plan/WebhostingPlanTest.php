<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Model\Plan;

use ParkManager\Bundle\CoreBundle\Test\Domain\EventsRecordingEntityAssertionTrait;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Event\WebhostingPlanConstraintsWasChanged;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuota;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingPlanTest extends TestCase
{
    use EventsRecordingEntityAssertionTrait;

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
        $id = $plan->getId();

        $plan2 = $this->createPlan();
        $plan2->changeConstraints($plan2->getConstraints());

        static::assertEquals($constraints, $plan->getConstraints());
        self::assertDomainEvents(
            $plan,
            [new WebhostingPlanConstraintsWasChanged($id, $constraints)]
        );
        self::assertNoDomainEvents($plan2);
    }

    /** @test */
    public function it_supports_setting_metadata(): void
    {
        $plan = $this->createPlan();
        $plan->withMetadata(['label' => 'Gold']);

        self::assertNoDomainEvents($plan);
        static::assertEquals(['label' => 'Gold'], $plan->getMetadata());
    }

    private function createPlan(): WebhostingPlan
    {
        return new WebhostingPlan(WebhostingPlanId::fromString(self::ID1), new Constraints());
    }
}
