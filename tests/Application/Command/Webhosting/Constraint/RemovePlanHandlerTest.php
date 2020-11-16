<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Webhosting\Constraint;

use ParkManager\Application\Command\Webhosting\Constraint\RemovePlan;
use ParkManager\Application\Command\Webhosting\Constraint\RemovePlanHandler;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Tests\Mock\Domain\Webhosting\PlanRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RemovePlanHandlerTest extends TestCase
{
    public const PLAN_ID1 = 'a18f57c9-6cf1-4c58-a176-935d012de5b0';
    public const PLAN_ID2 = 'fe6f130a-489e-49da-9357-136c7eb9933f';

    public const SPACE_ID_1 = 'ca91bc14-132c-4343-b47f-6aca1386a018';
    public const SPACE_ID_2 = '178dc20b-ce79-4ce2-a520-63d46a6668db';
    public const SPACE_ID_3 = '6af49ac2-11dd-49f8-b173-793c1f4606a5';

    private PlanRepositoryMock $planRepository;
    private SpaceRepositoryMock $spaceRepository;
    private RemovePlanHandler $handler;

    protected function setUp(): void
    {
        $plan1 = new Plan(PlanId::fromString(self::PLAN_ID1), new Constraints(['monthlyTraffic' => 10]));
        $plan2 = new Plan(PlanId::fromString(self::PLAN_ID2), new Constraints(['monthlyTraffic' => 50]));

        $space1 = SpaceRepositoryMock::createSpace(self::SPACE_ID_1);

        $space2 = SpaceRepositoryMock::createSpace(self::SPACE_ID_2);
        $space2->assignPlanWithConstraints($plan2, $plan2->constraints);

        $space3 = SpaceRepositoryMock::createSpace(self::SPACE_ID_3);
        $space3->assignCustomConstraints(new Constraints(['monthlyTraffic' => 100]));
        $space3->assignPlan($plan2);

        $this->planRepository = new PlanRepositoryMock([$plan1, $plan2]);
        $this->spaceRepository = new SpaceRepositoryMock([$space1, $space2, $space3]);
        $this->handler = new RemovePlanHandler($this->planRepository, $this->spaceRepository);
    }

    /** @test */
    public function it_removes_plan_without_any_spaces_assigned_to_the_plan(): void
    {
        $this->handler->__invoke(RemovePlan::with(self::PLAN_ID1));

        $this->planRepository->assertEntitiesWereRemoved([self::PLAN_ID1]);
        $this->spaceRepository->assertNoEntitiesWereSaved();
    }

    /** @test */
    public function it_removes_plan_and_clears_assignment_from_linked_spaces(): void
    {
        $this->handler->__invoke(RemovePlan::with(self::PLAN_ID2));

        $this->planRepository->assertEntitiesWereRemoved([self::PLAN_ID2]);
        $this->spaceRepository->assertEntityWasSavedThat(self::SPACE_ID_2, static fn (Space $space) => $space->plan === null && $space->constraints->equals(new Constraints(['monthlyTraffic' => 50])));
        $this->spaceRepository->assertEntityWasSavedThat(self::SPACE_ID_3, static fn (Space $space) => $space->plan === null && $space->constraints->equals(new Constraints(['monthlyTraffic' => 100])));
    }
}
