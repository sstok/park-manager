<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Webhosting\Constraint;

use ParkManager\Application\Command\Webhosting\Constraint\AssignPlanToSpace;
use ParkManager\Application\Command\Webhosting\Constraint\AssignPlanToSpaceHandler;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Application\Service\ApplicabilityCheckerMock;
use ParkManager\Tests\Mock\Domain\Webhosting\PlanRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AssignPlanToSpaceHandlerTest extends TestCase
{
    private const PLAN_ID1 = 'a18f57c9-6cf1-4c58-a176-935d012de5b0';
    private const PLAN_ID2 = 'fe6f130a-489e-49da-9357-136c7eb9933f';

    private const SPACE_ID_1 = 'ca91bc14-132c-4343-b47f-6aca1386a018';
    private const SPACE_ID_2 = '178dc20b-ce79-4ce2-a520-63d46a6668db';
    private const SPACE_ID_3 = '6af49ac2-11dd-49f8-b173-793c1f4606a5';

    private PlanRepositoryMock $planRepository;
    private SpaceRepositoryMock $spaceRepository;
    private AssignPlanToSpaceHandler $handler;
    private ApplicabilityCheckerMock $applicabilityChecker;

    protected function setUp(): void
    {
        $plan1 = new Plan(PlanId::fromString(self::PLAN_ID1), new Constraints(['monthlyTraffic' => 10]));
        $plan2 = new Plan(PlanId::fromString(self::PLAN_ID2), new Constraints(['monthlyTraffic' => 50]));

        $space1 = SpaceRepositoryMock::createSpace(self::SPACE_ID_1);
        $space1->assignPlanWithConstraints($plan1, $plan1->constraints);

        $space2 = SpaceRepositoryMock::createSpace(self::SPACE_ID_2);
        $space2->assignPlanWithConstraints($plan2, $plan1->constraints);

        $space3 = SpaceRepositoryMock::createSpace(self::SPACE_ID_3);

        $this->applicabilityChecker = new ApplicabilityCheckerMock();

        $this->planRepository = new PlanRepositoryMock([$plan1, $plan2]);
        $this->spaceRepository = new SpaceRepositoryMock([$space1, $space2, $space3]);
        $this->handler = new AssignPlanToSpaceHandler($this->planRepository, $this->spaceRepository, $this->applicabilityChecker);
    }

    /** @test */
    public function it_does_nothing_when_plan_equals_and_constraints_are_not_applied(): void
    {
        $this->handler->__invoke(AssignPlanToSpace::withoutConstraints(PlanId::fromString(self::PLAN_ID1), SpaceId::fromString(self::SPACE_ID_1)));
        $this->handler->__invoke(AssignPlanToSpace::withoutConstraints(PlanId::fromString(self::PLAN_ID2), SpaceId::fromString(self::SPACE_ID_2)));

        $this->spaceRepository->assertNoEntitiesWereSaved();
    }

    /** @test */
    public function it_assigns_new_plan_with_constraints(): void
    {
        $this->applicabilityChecker->mockForId = $spaceId = SpaceId::fromString(self::SPACE_ID_1);
        $this->applicabilityChecker->mockConstraints = $constraints = new Constraints(['monthlyTraffic' => 30, 'storageSize' => ByteSize::fromString('12 GiB')]);

        $this->handler->__invoke(AssignPlanToSpace::withConstraints($planId = PlanId::fromString(self::PLAN_ID2), $spaceId));

        $this->spaceRepository->assertEntityWasSavedThat($spaceId,
            static fn (Space $space): bool => PlanId::equalsValueOfEntity($planId, $space->plan, 'id') &&
                $space->constraints->equals($constraints)
        );
    }

    /** @test */
    public function it_assigns_same_plan_with_new_constraints(): void
    {
        $plan = $this->planRepository->get($planId = PlanId::fromString(self::PLAN_ID1));
        $plan->changeConstraints($constraints = new Constraints(['monthlyTraffic' => 35, 'storageSize' => ByteSize::fromString('24 GiB')]));
        $this->planRepository->save($plan);

        $spaceId = SpaceId::fromString(self::SPACE_ID_1);

        $this->handler->__invoke(AssignPlanToSpace::withConstraints($planId, $spaceId));

        $this->spaceRepository->assertEntityWasSavedThat($spaceId,
            static fn (Space $space): bool => PlanId::equalsValueOfEntity($planId, $space->plan, 'id') &&
                $space->constraints->equals($constraints)
        );
    }

    /** @test */
    public function it_assigns_new_plan_without_constraints(): void
    {
        $this->applicabilityChecker->mockForId = $spaceId = SpaceId::fromString(self::SPACE_ID_1);
        $this->applicabilityChecker->mockConstraints = new Constraints(['monthlyTraffic' => 30, 'storageSize' => ByteSize::fromString('12 GiB')]);

        $this->handler->__invoke(AssignPlanToSpace::withoutConstraints($planId = PlanId::fromString(self::PLAN_ID2), $spaceId));

        $this->spaceRepository->assertEntityWasSavedThat($spaceId,
            static fn (Space $space): bool => PlanId::equalsValueOfEntity($planId, $space->plan, 'id') &&
                $space->constraints->equals(new Constraints(['monthlyTraffic' => 10]))
        );
    }
}
