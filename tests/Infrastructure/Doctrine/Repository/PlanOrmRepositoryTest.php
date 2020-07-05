<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Exception\PlanNotFound;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Infrastructure\Doctrine\Repository\PlanOrmRepository;
use ParkManager\Tests\Infrastructure\Doctrine\EntityRepositoryTestCase;

/**
 * @internal
 *
 * @group functional
 */
final class PlanOrmRepositoryTest extends EntityRepositoryTestCase
{
    private const SET_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';
    private const SET_ID2 = '3bd0fa08-a756-11e7-bdf0-acbc32b58315';

    /** @test */
    public function it_gets_existing_constraint_sets(): void
    {
        $repository = $this->createRepository();
        $this->setUpPlan1($repository);
        $this->setUpPlan2($repository);

        $id = PlanId::fromString(self::SET_ID1);
        $id2 = PlanId::fromString(self::SET_ID2);

        $plan = $repository->get($id);
        $plan2 = $repository->get($id2);

        self::assertEquals($id, $plan->getId());
        self::assertEquals(['title' => 'Supper Gold XL'], $plan->getMetadata());
        self::assertTrue($plan->getConstraints()->equals((new Constraints())->setMonthlyTraffic(5)));

        self::assertEquals($id2, $plan2->getId());
        self::assertEquals([], $plan2->getMetadata());
        self::assertTrue($plan2->getConstraints()->equals((new Constraints())->setMonthlyTraffic(5)));
    }

    /** @test */
    public function it_removes_an_existing_constraint_set(): void
    {
        $repository = $this->createRepository();
        $this->setUpPlan1($repository);
        $this->setUpPlan2($repository);

        $id = PlanId::fromString(self::SET_ID1);
        $id2 = PlanId::fromString(self::SET_ID2);
        $plan = $repository->get($id);

        $repository->remove($plan);

        $repository->get($id2);

        // Assert actually removed
        $this->expectException(PlanNotFound::class);
        $this->expectExceptionMessage(PlanNotFound::withId($id)->getMessage());
        $repository->get($id);
    }

    private function createRepository(): PlanOrmRepository
    {
        return new PlanOrmRepository($this->getEntityManager());
    }

    private function setUpPlan1(PlanOrmRepository $repository): void
    {
        $plan = new Plan(
            PlanId::fromString(self::SET_ID1),
            (new Constraints())->setMonthlyTraffic(5)
        );
        $plan->withMetadata(['title' => 'Supper Gold XL']);

        $repository->save($plan);
    }

    private function setUpPlan2(PlanOrmRepository $repository): void
    {
        $repository->save(
            new Plan(
                PlanId::fromString(self::SET_ID2),
                (new Constraints())->setMonthlyTraffic(5)
            )
        );
    }
}
