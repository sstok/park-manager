<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Doctrine\Plan;

use ParkManager\Bundle\CoreBundle\Test\Doctrine\EntityRepositoryTestCase;
use ParkManager\Bundle\CoreBundle\Test\Domain\EventSourcedRepositoryTestHelper;
use ParkManager\Bundle\WebhostingBundle\Doctrine\Plan\WebhostingPlanOrmRepository;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Exception\WebhostingPlanNotFound;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuota;

/**
 * @internal
 *
 * @group functional
 */
final class WebhostingPlaneOrmRepositoryTest extends EntityRepositoryTestCase
{
    use EventSourcedRepositoryTestHelper;

    private const PLAN_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';
    private const PLAN_ID2 = '3bd0fa08-a756-11e7-bdf0-acbc32b58315';

    /** @test */
    public function it_gets_existing_plans(): void
    {
        $repository = $this->createRepository(2);
        $this->setUpPlan1($repository);
        $this->setUpPlan2($repository);

        $id  = WebhostingPlanId::fromString(self::PLAN_ID1);
        $id2 = WebhostingPlanId::fromString(self::PLAN_ID2);

        $plan  = $repository->get($id);
        $plan2 = $repository->get($id2);

        self::assertEquals($id, $plan->id());
        self::assertEquals(['title' => 'Supper Gold XL'], $plan->metadata());
        self::assertEquals(new Constraints(new MonthlyTrafficQuota(5)), $plan->constraints());

        self::assertEquals($id2, $plan2->id());
        self::assertEquals([], $plan2->metadata());
        self::assertEquals(new Constraints(new MonthlyTrafficQuota(50)), $plan2->constraints());
    }

    /** @test */
    public function it_removes_an_existing_plan(): void
    {
        $repository = $this->createRepository(2);
        $this->setUpPlan1($repository);
        $this->setUpPlan2($repository);

        $id      = WebhostingPlanId::fromString(self::PLAN_ID1);
        $id2     = WebhostingPlanId::fromString(self::PLAN_ID2);
        $plan = $repository->get($id);

        $repository->remove($plan);

        $repository->get($id2);

        // Assert actually removed
        $this->expectException(WebhostingPlanNotFound::class);
        $this->expectExceptionMessage(WebhostingPlanNotFound::withId($id)->getMessage());
        $repository->get($id);
    }

    private function createRepository(int $expectedEventsCount): WebhostingPlanOrmRepository
    {
        return new WebhostingPlanOrmRepository(
            $this->getEntityManager(),
            $this->createEventsExpectingEventBus()
        );
    }

    private function setUpPlan1(WebhostingPlanOrmRepository $repository): void
    {
        $plan = WebhostingPlan::create(
            WebhostingPlanId::fromString(self::PLAN_ID1),
            new Constraints(new MonthlyTrafficQuota(5))
        );
        $plan->withMetadata(['title' => 'Supper Gold XL']);

        $repository->save($plan);
    }

    private function setUpPlan2(WebhostingPlanOrmRepository $repository): void
    {
        $repository->save(
            WebhostingPlan::create(
                WebhostingPlanId::fromString(self::PLAN_ID2),
                new Constraints(new MonthlyTrafficQuota(50))
            )
        );
    }
}
