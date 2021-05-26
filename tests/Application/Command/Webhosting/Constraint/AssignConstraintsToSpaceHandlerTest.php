<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Webhosting\Constraint;

use ParkManager\Application\Command\Webhosting\Constraint\AssignConstraintsToSpace;
use ParkManager\Application\Command\Webhosting\Constraint\AssignConstraintsToSpaceHandler;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Application\Service\ApplicabilityCheckerMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AssignConstraintsToSpaceHandlerTest extends TestCase
{
    private const SPACE_ID_1 = 'ca91bc14-132c-4343-b47f-6aca1386a018';
    private const SPACE_ID_2 = '178dc20b-ce79-4ce2-a520-63d46a6668db';
    private const SPACE_ID_3 = '6af49ac2-11dd-49f8-b173-793c1f4606a5';

    private SpaceRepositoryMock $spaceRepository;
    private ApplicabilityCheckerMock $applicabilityChecker;
    private AssignConstraintsToSpaceHandler $handler;

    protected function setUp(): void
    {
        $plan1 = new Plan(PlanId::fromString('a18f57c9-6cf1-4c58-a176-935d012de5b0'), new Constraints(['monthlyTraffic' => 10]));

        $space1 = SpaceRepositoryMock::createSpace(self::SPACE_ID_1);
        $space1->assignPlanWithConstraints($plan1, $plan1->constraints);

        $space2 = SpaceRepositoryMock::createSpace(self::SPACE_ID_2);
        $space2->assignCustomConstraints(new Constraints(['monthlyTraffic' => 10]));

        $space3 = SpaceRepositoryMock::createSpace(self::SPACE_ID_3);
        $space3->assignCustomConstraints(new Constraints(['monthlyTraffic' => 10]));

        $this->spaceRepository = new SpaceRepositoryMock([$space1, $space2, $space3]);
        $this->applicabilityChecker = new ApplicabilityCheckerMock();

        $this->handler = new AssignConstraintsToSpaceHandler($this->spaceRepository, $this->applicabilityChecker);
    }

    /** @test */
    public function it_does_nothing_when_constraints_equals_and_no_plan_was_assigned(): void
    {
        $this->handler->__invoke(new AssignConstraintsToSpace(SpaceId::fromString(self::SPACE_ID_3), new Constraints(['monthlyTraffic' => 10])));

        $this->spaceRepository->assertNoEntitiesWereSaved();
    }

    /** @test */
    public function it_assigns_new_constraints(): void
    {
        $this->handler->__invoke(new AssignConstraintsToSpace($id1 = SpaceId::fromString(self::SPACE_ID_1), $constraints1 = new Constraints(['monthlyTraffic' => 10])));
        $this->handler->__invoke(new AssignConstraintsToSpace($id2 = SpaceId::fromString(self::SPACE_ID_2), $constraints2 = new Constraints(['monthlyTraffic' => 30])));

        $this->spaceRepository->assertEntitiesCountWasSaved(2);
        $this->spaceRepository->assertEntityWasSavedThat($id1, static fn (Space $space): bool => $space->constraints->equals($constraints1) && $space->plan === null);
        $this->spaceRepository->assertEntityWasSavedThat($id2, static fn (Space $space): bool => $space->constraints->equals($constraints2) && $space->plan === null);
    }
}
