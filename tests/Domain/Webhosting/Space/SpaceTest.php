<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Space;

use DateTimeImmutable;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceId;
use ParkManager\Domain\Webhosting\Plan\Constraints;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlan;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlanId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SpaceTest extends TestCase
{
    private const SPACE_ID = '374dd50e-9b9f-11e7-9730-acbc32b58315';

    private const OWNER_ID1 = '2a9cd25c-97ca-11e7-9683-acbc32b58315';
    private const OWNER_ID2 = 'ce18c388-9ba2-11e7-b15f-acbc32b58315';

    private const PLAN_ID_1 = '654665ea-9869-11e7-9563-acbc32b58315';
    private const PLAN_ID_2 = 'f5788aae-9aed-11e7-a3c9-acbc32b58315';

    /** @test */
    public function it_registers_an_webhosting_space(): void
    {
        $id = WebhostingSpaceId::create();
        $constraints = new Constraints();
        $plan = $this->createWebhostingPlan($constraints);

        $space = Space::register($id, $owner = OwnerId::fromString(self::OWNER_ID1), $plan);

        static::assertEquals($id, $space->getId());
        static::assertEquals($owner, $space->getOwner());
        static::assertSame($plan, $space->getPlan());
        static::assertSame($constraints, $space->getPlanConstraints());
    }

    /** @test */
    public function it_registers_an_webhosting_space_with_custom_constraints(): void
    {
        $id = WebhostingSpaceId::create();
        $constraints = new Constraints();

        $space = Space::registerWithCustomConstraints($id, $owner = OwnerId::fromString(self::OWNER_ID1), $constraints);

        static::assertEquals($id, $space->getId());
        static::assertEquals($owner, $space->getOwner());
        static::assertSame($constraints, $space->getPlanConstraints());
        static::assertNull($space->getPlan());
    }

    /** @test */
    public function it_allows_changing_plan_assignment(): void
    {
        $id2 = WebhostingSpaceId::create();
        $constraints1 = new Constraints();
        $constraints2 = new Constraints(new MonthlyTrafficQuota(50));
        $plan1 = $this->createWebhostingPlan($constraints1);
        $plan2 = $this->createWebhostingPlan($constraints2, self::PLAN_ID_2);
        $space1 = Space::register(WebhostingSpaceId::create(), OwnerId::fromString(self::OWNER_ID1), $plan1);
        $space2 = Space::register($id2, OwnerId::fromString(self::OWNER_ID1), $plan1);

        $space1->assignPlan($plan1);
        $space2->assignPlan($plan2);

        static::assertSame($plan1, $space1->getPlan(), 'Plan should not change');
        static::assertSame($plan1->getConstraints(), $space1->getPlanConstraints(), 'Constraints should not change');

        static::assertSame($plan2, $space2->getPlan());
        static::assertSame($plan1->getConstraints(), $space2->getPlanConstraints());
    }

    /** @test */
    public function it_allows_changing_plan_assignment_with_constraints(): void
    {
        $id2 = WebhostingSpaceId::create();
        $constraints1 = new Constraints();
        $constraints2 = new Constraints(new MonthlyTrafficQuota(50));
        $plan1 = $this->createWebhostingPlan($constraints1);
        $plan2 = $this->createWebhostingPlan($constraints2, self::PLAN_ID_2);
        $space1 = Space::register(WebhostingSpaceId::create(), OwnerId::fromString(self::OWNER_ID1), $plan1);
        $space2 = Space::register($id2, OwnerId::fromString(self::OWNER_ID1), $plan1);

        $space1->assignPlanWithConstraints($plan1);
        $space2->assignPlanWithConstraints($plan2);

        static::assertSame($plan1, $space1->getPlan(), 'Plan should not change');
        static::assertSame($plan1->getConstraints(), $space1->getPlanConstraints(), 'Constraints should not change');

        static::assertSame($plan2, $space2->getPlan());
        static::assertSame($plan2->getConstraints(), $space2->getPlanConstraints());
    }

    /** @test */
    public function it_updates_space_when_assigning_plan_Constraints_are_different(): void
    {
        $id = WebhostingSpaceId::create();
        $plan = $this->createWebhostingPlan(new Constraints());
        $space = Space::register($id, OwnerId::fromString(self::OWNER_ID1), $plan);

        $plan->changeConstraints($newConstraints = new Constraints(new MonthlyTrafficQuota(50)));
        $space->assignPlanWithConstraints($plan);

        static::assertSame($plan, $space->getPlan());
        static::assertSame($plan->getConstraints(), $space->getPlanConstraints());
    }

    /** @test */
    public function it_allows_assigning_custom_specification(): void
    {
        $id = WebhostingSpaceId::create();
        $plan = $this->createWebhostingPlan(new Constraints());
        $space = Space::register($id, OwnerId::fromString(self::OWNER_ID1), $plan);

        $space->assignCustomConstraints($newConstraints = new Constraints(new MonthlyTrafficQuota(50)));

        static::assertNull($space->getPlan());
        static::assertSame($newConstraints, $space->getPlanConstraints());
    }

    /** @test */
    public function it_allows_changing_custom_specification(): void
    {
        $id = WebhostingSpaceId::create();
        $space = Space::registerWithCustomConstraints($id, OwnerId::fromString(self::OWNER_ID1), new Constraints());

        $space->assignCustomConstraints($newConstraints = new Constraints(new MonthlyTrafficQuota(50)));

        static::assertNull($space->getPlan());
        static::assertSame($newConstraints, $space->getPlanConstraints());
    }

    /** @test */
    public function it_does_not_update_space_Constraints_when_assigning_Constraints_are_same(): void
    {
        $id = WebhostingSpaceId::create();
        $Constraints = new Constraints();
        $space = Space::registerWithCustomConstraints($id, OwnerId::fromString(self::OWNER_ID1), $Constraints);

        $space->assignCustomConstraints($Constraints);

        static::assertNull($space->getPlan());
        static::assertSame($Constraints, $space->getPlanConstraints());
    }

    /** @test */
    public function it_supports_switching_the_space_owner(): void
    {
        $space1 = Space::register(
            WebhostingSpaceId::fromString(self::SPACE_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Constraints())
        );
        $space2 = Space::register(
            $id2 = WebhostingSpaceId::fromString(self::SPACE_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Constraints())
        );

        $space1->switchOwner($owner1 = OwnerId::fromString(self::OWNER_ID1));
        $space2->switchOwner($owner2 = OwnerId::fromString(self::OWNER_ID2));

        static::assertEquals($owner1, $space1->getOwner());
        static::assertEquals($owner2, $space2->getOwner());
    }

    /** @test */
    public function it_allows_being_marked_for_removal(): void
    {
        $space1 = Space::register(
            WebhostingSpaceId::fromString(self::SPACE_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Constraints())
        );
        $space2 = Space::register(
            $id2 = WebhostingSpaceId::fromString(self::SPACE_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Constraints())
        );

        $space2->markForRemoval();
        $space2->markForRemoval();

        static::assertFalse($space1->isMarkedForRemoval());
        static::assertTrue($space2->isMarkedForRemoval());
    }

    /** @test */
    public function it_can_expire(): void
    {
        $space1 = Space::register(
            WebhostingSpaceId::fromString(self::SPACE_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Constraints())
        );
        $space2 = Space::register(
            $id2 = WebhostingSpaceId::fromString(self::SPACE_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Constraints())
        );

        $space2->setExpirationDate($date = new DateTimeImmutable('now +6 days'));

        static::assertFalse($space1->isExpired());
        static::assertFalse($space1->isExpired($date->modify('+2 days')));

        static::assertFalse($space2->isExpired($date->modify('-10 days')));
        static::assertTrue($space2->isExpired($date));
        static::assertTrue($space2->isExpired($date->modify('+2 days')));

        $space1->removeExpirationDate();
        $space2->removeExpirationDate();

        static::assertFalse($space1->isExpired());
        static::assertFalse($space2->isExpired($date));
        static::assertFalse($space2->isExpired($date->modify('+2 days')));
    }

    private function createWebhostingPlan(Constraints $Constraints, string $id = self::PLAN_ID_1): WebhostingPlan
    {
        return new WebhostingPlan(WebhostingPlanId::fromString($id), $Constraints);
    }
}
