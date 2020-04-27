<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Space;

use DateTimeImmutable;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\ConstraintSetId;
use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSet;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SpaceTest extends TestCase
{
    private const SPACE_ID = '374dd50e-9b9f-11e7-9730-acbc32b58315';

    private const OWNER_ID1 = '2a9cd25c-97ca-11e7-9683-acbc32b58315';
    private const OWNER_ID2 = 'ce18c388-9ba2-11e7-b15f-acbc32b58315';

    private const SET_ID_1 = '654665ea-9869-11e7-9563-acbc32b58315';
    private const SET_ID_2 = 'f5788aae-9aed-11e7-a3c9-acbc32b58315';

    /** @test */
    public function it_registers_an_webhosting_space(): void
    {
        $id = SpaceId::create();
        $constraints = new Constraints();
        $constraintSet = $this->createSharedConstraintSet($constraints);
        $owner = UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1);

        $space = Space::register($id, $owner, $constraintSet);

        self::assertEquals($id, $space->getId());
        self::assertEquals($owner, $space->getOwner());
        self::assertSame($constraintSet, $space->getAssignedConstraintSet());
        self::assertSame($constraints, $space->getConstraints());
    }

    /** @test */
    public function it_registers_an_webhosting_space_with_custom_constraints(): void
    {
        $owner = UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1);
        $id = SpaceId::create();
        $constraints = new Constraints();

        $space = Space::registerWithCustomConstraints($id, $owner, $constraints);

        self::assertEquals($id, $space->getId());
        self::assertEquals($owner, $space->getOwner());
        self::assertSame($constraints, $space->getConstraints());
        self::assertNull($space->getAssignedConstraintSet());
    }

    /** @test */
    public function it_allows_changing_constraint_set_assignment(): void
    {
        $owner = UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1);
        $constraints1 = new Constraints();
        $constraints2 = new Constraints(new MonthlyTrafficQuota(50));
        $constraintSet1 = $this->createSharedConstraintSet($constraints1);
        $constraintSet2 = $this->createSharedConstraintSet($constraints2, self::SET_ID_2);
        $space1 = Space::register(SpaceId::create(), $owner, $constraintSet1);
        $space2 = Space::register(SpaceId::create(), $owner, $constraintSet1);

        $space1->assignConstraintSet($constraintSet1);
        $space2->assignConstraintSet($constraintSet2);

        self::assertSame($constraintSet1, $space1->getAssignedConstraintSet(), 'ConstraintSet should not change');
        self::assertSame($constraintSet1->getConstraints(), $space1->getConstraints(), 'Constraints should not change');

        self::assertSame($constraintSet2, $space2->getAssignedConstraintSet());
        self::assertSame($constraintSet1->getConstraints(), $space2->getConstraints());
    }

    /** @test */
    public function it_allows_changing_constraint_set_assignment_with_constraints(): void
    {
        $owner = UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1);
        $constraints1 = new Constraints();
        $constraints2 = new Constraints(new MonthlyTrafficQuota(50));
        $constraintSet1 = $this->createSharedConstraintSet($constraints1);
        $constraintSet2 = $this->createSharedConstraintSet($constraints2, self::SET_ID_2);
        $space1 = Space::register(SpaceId::create(), $owner, $constraintSet1);
        $space2 = Space::register(SpaceId::create(), $owner, $constraintSet1);

        $space1->assignSetWithConstraints($constraintSet1);
        $space2->assignSetWithConstraints($constraintSet2);

        self::assertSame($constraintSet1, $space1->getAssignedConstraintSet(), 'ConstraintSet should not change');
        self::assertSame($constraintSet1->getConstraints(), $space1->getConstraints(), 'Constraints should not change');

        self::assertSame($constraintSet2, $space2->getAssignedConstraintSet());
        self::assertSame($constraintSet2->getConstraints(), $space2->getConstraints());
    }

    /** @test */
    public function it_updates_space_when_assigning_constraint_set_constraints_are_different(): void
    {
        $constraintSet = $this->createSharedConstraintSet(new Constraints());
        $space = Space::register(
            SpaceId::create(),
            UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1),
            $constraintSet
        );

        $constraintSet->changeConstraints($newConstraints = new Constraints(new MonthlyTrafficQuota(50)));
        $space->assignSetWithConstraints($constraintSet);

        self::assertSame($constraintSet, $space->getAssignedConstraintSet());
        self::assertSame($constraintSet->getConstraints(), $space->getConstraints());
    }

    /** @test */
    public function it_allows_assigning_custom_specification(): void
    {
        $constraintSet = $this->createSharedConstraintSet(new Constraints());
        $space = Space::register(
            SpaceId::create(),
            UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1),
            $constraintSet
        );

        $space->assignCustomConstraints($newConstraints = new Constraints(new MonthlyTrafficQuota(50)));

        self::assertNull($space->getAssignedConstraintSet());
        self::assertSame($newConstraints, $space->getConstraints());
    }

    /** @test */
    public function it_allows_changing_custom_specification(): void
    {
        $space = Space::registerWithCustomConstraints(
            SpaceId::create(),
            UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1),
            new Constraints()
        );

        $space->assignCustomConstraints($newConstraints = new Constraints(new MonthlyTrafficQuota(50)));

        self::assertNull($space->getAssignedConstraintSet());
        self::assertSame($newConstraints, $space->getConstraints());
    }

    /** @test */
    public function it_does_not_update_space_constraints_when_assigning_constraints_are_same(): void
    {
        $constraints = new Constraints();
        $space = Space::registerWithCustomConstraints(
            SpaceId::create(),
            UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1),
            $constraints
        );

        $space->assignCustomConstraints($constraints);

        self::assertNull($space->getAssignedConstraintSet());
        self::assertSame($constraints, $space->getConstraints());
    }

    /** @test */
    public function it_supports_switching_the_space_owner(): void
    {
        $owner1 = UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1);
        $owner2 = UserRepositoryMock::createUser('joHn@example.com', self::OWNER_ID2);
        $space1 = Space::register(
            SpaceId::fromString(self::SPACE_ID),
            $owner1,
            $this->createSharedConstraintSet(new Constraints())
        );
        $space2 = Space::register(
            $id2 = SpaceId::fromString(self::SPACE_ID),
            $owner1,
            $this->createSharedConstraintSet(new Constraints())
        );

        $space1->switchOwner($owner1);
        $space2->switchOwner($owner2);

        self::assertEquals($owner1, $space1->getOwner());
        self::assertEquals($owner2, $space2->getOwner());
    }

    /** @test */
    public function it_allows_being_marked_for_removal(): void
    {
        $owner = UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1);
        $space1 = Space::register(
            SpaceId::fromString(self::SPACE_ID),
            $owner,
            $this->createSharedConstraintSet(new Constraints())
        );
        $space2 = Space::register(
            $id2 = SpaceId::fromString(self::SPACE_ID),
            $owner,
            $this->createSharedConstraintSet(new Constraints())
        );

        $space2->markForRemoval();
        $space2->markForRemoval();

        self::assertFalse($space1->isMarkedForRemoval());
        self::assertTrue($space2->isMarkedForRemoval());
    }

    /** @test */
    public function it_can_expire(): void
    {
        $owner = UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1);
        $space1 = Space::register(
            SpaceId::fromString(self::SPACE_ID),
            $owner,
            $this->createSharedConstraintSet(new Constraints())
        );
        $space2 = Space::register(
            $id2 = SpaceId::fromString(self::SPACE_ID),
            $owner,
            $this->createSharedConstraintSet(new Constraints())
        );

        $space2->setExpirationDate($date = new DateTimeImmutable('now +6 days'));

        self::assertFalse($space1->isExpired());
        self::assertFalse($space1->isExpired($date->modify('+2 days')));

        self::assertFalse($space2->isExpired($date->modify('-10 days')));
        self::assertTrue($space2->isExpired($date));
        self::assertTrue($space2->isExpired($date->modify('+2 days')));

        $space1->removeExpirationDate();
        $space2->removeExpirationDate();

        self::assertFalse($space1->isExpired());
        self::assertFalse($space2->isExpired($date));
        self::assertFalse($space2->isExpired($date->modify('+2 days')));
    }

    private function createSharedConstraintSet(Constraints $constraints, string $id = self::SET_ID_1): SharedConstraintSet
    {
        return new SharedConstraintSet(ConstraintSetId::fromString($id), $constraints);
    }
}
