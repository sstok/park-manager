<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Model\Plan\Event;

use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Event\WebhostingPlanConstraintsWasChanged;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuota;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\StorageSpaceQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingPlanConstraintsWasChangedTest extends TestCase
{
    private const PLAN_ID = 'b3e3846a-97c6-11e7-bf67-acbc32b58315';

    /** @test */
    public function its_constructable(): void
    {
        $event = new WebhostingPlanConstraintsWasChanged(
            $id = WebhostingPlanId::fromString(self::PLAN_ID),
            $constraints = new Constraints(new StorageSpaceQuota('5G'), new MonthlyTrafficQuota(50))
        );

        self::assertTrue($id->equals($event->id()));
        self::assertEquals($constraints, $event->constraints());
    }
}
