<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Webhosting\Constraint;

use ParkManager\Application\Command\Webhosting\Constraint\AssignPlanToSpace;
use ParkManager\Application\Command\Webhosting\Constraint\UpdatePlan;
use ParkManager\Application\Command\Webhosting\Constraint\UpdatePlanHandler;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Application\Service\SpyingMessageBus;
use ParkManager\Tests\Mock\Domain\Webhosting\PlanRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UpdatePlanHandlerTest extends TestCase
{
    public const PLAN_ID1 = 'a18f57c9-6cf1-4c58-a176-935d012de5b0';
    public const PLAN_ID2 = 'fe6f130a-489e-49da-9357-136c7eb9933f';

    public const SPACE_ID_1 = 'ca91bc14-132c-4343-b47f-6aca1386a018';
    public const SPACE_ID_2 = '178dc20b-ce79-4ce2-a520-63d46a6668db';
    public const SPACE_ID_3 = '6af49ac2-11dd-49f8-b173-793c1f4606a5';

    private PlanRepositoryMock $planRepository;
    private SpaceRepositoryMock $spaceRepository;
    private SpyingMessageBus $messageBus;
    private UpdatePlanHandler $handler;

    protected function setUp(): void
    {
        $plan1 = new Plan(PlanId::fromString(self::PLAN_ID1), new Constraints(['monthlyTraffic' => 10]));
        $plan1->withMetadata(['price' => 'less']);

        $plan2 = new Plan(PlanId::fromString(self::PLAN_ID2), new Constraints(['monthlyTraffic' => 50]));

        $space1 = SpaceRepositoryMock::createSpace(self::SPACE_ID_1);
        $space1->assignPlan($plan2);

        $space2 = SpaceRepositoryMock::createSpace(self::SPACE_ID_2);
        $space2->assignPlan($plan2);

        $space3 = SpaceRepositoryMock::createSpace(self::SPACE_ID_3);
        $space3->assignPlan($plan1);

        $this->planRepository = new PlanRepositoryMock([$plan1, $plan2]);
        $this->spaceRepository = new SpaceRepositoryMock([$space1, $space2, $space3]);
        $this->messageBus = new SpyingMessageBus();
        $this->handler = new UpdatePlanHandler($this->planRepository, $this->spaceRepository, $this->messageBus);
    }

    /** @test */
    public function handles_updating_plan_without_new_metadata(): void
    {
        $this->handler->__invoke(new UpdatePlan(PlanId::fromString(self::PLAN_ID1), $constraint = new Constraints(['monthlyTraffic' => 70]), null, false));

        $plan = new Plan(PlanId::fromString(self::PLAN_ID1), $constraint);
        $plan->withMetadata(['price' => 'less']);

        $this->planRepository->assertEntitiesCountWasSaved(1);
        $this->planRepository->assertEntitiesWereSaved([$plan]);

        self::assertCount(0, $this->messageBus->dispatchedMessages);
    }

    /** @test */
    public function handles_updating_plan_without_linked_spaces(): void
    {
        $this->handler->__invoke(new UpdatePlan($id = PlanId::fromString(self::PLAN_ID2), $constraint = new Constraints(['monthlyTraffic' => 70]), ['label' => 'I have'], false));

        $plan = new Plan($id, $constraint);
        $plan->withMetadata(['label' => 'I have']);

        $this->planRepository->assertEntitiesCountWasSaved(1);
        $this->planRepository->assertEntitiesWereSaved([$plan]);

        self::assertCount(0, $this->messageBus->dispatchedMessages);
    }

    /** @test */
    public function handles_updating_plan_with_linked_spaces(): void
    {
        $this->handler->__invoke(new UpdatePlan($planId = PlanId::fromString(self::PLAN_ID2), $constraint = new Constraints(['monthlyTraffic' => 70]), ['label' => 'I have'], true));

        $plan = new Plan($planId, $constraint);
        $plan->withMetadata(['label' => 'I have']);

        $this->planRepository->assertEntitiesCountWasSaved(1);
        $this->planRepository->assertEntitiesWereSaved([$plan]);

        self::assertEquals([
            AssignPlanToSpace::withConstraints($planId, SpaceId::fromString(self::SPACE_ID_1)),
            AssignPlanToSpace::withConstraints($planId, SpaceId::fromString(self::SPACE_ID_2)),
        ], $this->messageBus->dispatchedMessages);
    }
}
