<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Model\Account\Event;

use ParkManager\Bundle\WebhostingBundle\Model\Account\Event\WebhostingAccountPlanAssignmentWasChanged;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuota;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingAccountPlanAssignmentWasChangedTest extends TestCase
{
    private const WEBHOSTING_PLAN_ID = 'b3e3846a-97c6-11e7-bf67-acbc32b58315';
    private const ACCOUNT_ID = 'b288e23c-97c5-11e7-b51a-acbc32b58315';

    /** @test */
    public function its_constructable(): void
    {
        $event = new WebhostingAccountPlanAssignmentWasChanged(
            $id = $this->createAccountId(),
            $plan = $this->createWebhostingPlan()
        );

        static::assertTrue($id->equals($event->account));
        static::assertEquals($plan->getId(), $event->plan);
        static::assertNull($event->planConstraints);
    }

    /** @test */
    public function its_constructable_with_constraints_provided(): void
    {
        $event = WebhostingAccountPlanAssignmentWasChanged::withConstraints(
            $id = $this->createAccountId(),
            $plan = $this->createWebhostingPlan()
        );

        static::assertTrue($id->equals($event->account));
        static::assertEquals($plan->getId(), $event->plan);
        static::assertEquals($plan->getConstraints(), $event->planConstraints);
    }

    private function createWebhostingPlan(): WebhostingPlan
    {
        return new WebhostingPlan(
            WebhostingPlanId::fromString(self::WEBHOSTING_PLAN_ID),
            new Constraints(new StorageSpaceQuota('5G'), new MonthlyTrafficQuota(50))
        );
    }

    private function createAccountId(): WebhostingAccountId
    {
        return WebhostingAccountId::fromString(self::ACCOUNT_ID);
    }
}
