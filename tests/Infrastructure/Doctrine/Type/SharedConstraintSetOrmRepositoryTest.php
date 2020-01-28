<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Type;

use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Exception\ConstraintSetNotFound;
use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSet;
use ParkManager\Domain\Webhosting\Constraint\ConstraintSetId;
use ParkManager\Infrastructure\Doctrine\Repository\SharedConstraintSetOrmRepository;
use ParkManager\Tests\Infrastructure\Doctrine\EntityRepositoryTestCase;

/**
 * @internal
 *
 * @group functional
 */
final class SharedConstraintSetOrmRepositoryTest extends EntityRepositoryTestCase
{
    private const SET_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';
    private const SET_ID2 = '3bd0fa08-a756-11e7-bdf0-acbc32b58315';

    /** @test */
    public function it_gets_existing_constraintSets(): void
    {
        $repository = $this->createRepository();
        $this->setUpConstraintSet1($repository);
        $this->setUpConstraintSet2($repository);

        $id = ConstraintSetId::fromString(self::SET_ID1);
        $id2 = ConstraintSetId::fromString(self::SET_ID2);

        $constraintSet = $repository->get($id);
        $constraintSet2 = $repository->get($id2);

        static::assertEquals($id, $constraintSet->getId());
        static::assertEquals(['title' => 'Supper Gold XL'], $constraintSet->getMetadata());
        static::assertEquals(new Constraints(new MonthlyTrafficQuota(5)), $constraintSet->getConstraints());

        static::assertEquals($id2, $constraintSet2->getId());
        static::assertEquals([], $constraintSet2->getMetadata());
        static::assertEquals(new Constraints(new MonthlyTrafficQuota(50)), $constraintSet2->getConstraints());
    }

    /** @test */
    public function it_removes_an_existing_constraintSet(): void
    {
        $repository = $this->createRepository();
        $this->setUpConstraintSet1($repository);
        $this->setUpConstraintSet2($repository);

        $id = ConstraintSetId::fromString(self::SET_ID1);
        $id2 = ConstraintSetId::fromString(self::SET_ID2);
        $constraintSet = $repository->get($id);

        $repository->remove($constraintSet);

        $repository->get($id2);

        // Assert actually removed
        $this->expectException(ConstraintSetNotFound::class);
        $this->expectExceptionMessage(ConstraintSetNotFound::withId($id)->getMessage());
        $repository->get($id);
    }

    private function createRepository(): SharedConstraintSetOrmRepository
    {
        return new SharedConstraintSetOrmRepository($this->getEntityManager());
    }

    private function setUpConstraintSet1(SharedConstraintSetOrmRepository $repository): void
    {
        $constraintSet = new SharedConstraintSet(
            ConstraintSetId::fromString(self::SET_ID1),
            new Constraints(new MonthlyTrafficQuota(5))
        );
        $constraintSet->withMetadata(['title' => 'Supper Gold XL']);

        $repository->save($constraintSet);
    }

    private function setUpConstraintSet2(SharedConstraintSetOrmRepository $repository): void
    {
        $repository->save(
            new SharedConstraintSet(
                ConstraintSetId::fromString(self::SET_ID2),
                new Constraints(new MonthlyTrafficQuota(50))
            )
        );
    }
}
