<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Space\Exception\CannotRemoveActiveWebhostingSpace;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSet;
use ParkManager\Domain\Webhosting\Constraint\ConstraintSetId;
use ParkManager\Infrastructure\Doctrine\Repository\WebhostingSpaceOrmRepository;
use ParkManager\Tests\Infrastructure\Doctrine\EntityRepositoryTestCase;

/**
 * @internal
 *
 * @group functional
 */
final class WebhostingSpaceOrmRepositoryTest extends EntityRepositoryTestCase
{
    private const OWNER_ID1 = '3f8da982-a528-11e7-a2da-acbc32b58315';
    private const SET_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';

    private const SPACE_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const SPACE_ID2 = '47f6db14-a69c-11e7-be13-acbc32b58315';

    /** @var Constraints */
    private $constraintSetConstraints;

    /** @var SharedConstraintSet */
    private $constraintSet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->constraintSetConstraints = new Constraints(new MonthlyTrafficQuota(50));
        $this->constraintSet = new SharedConstraintSet(
            ConstraintSetId::fromString(self::SET_ID1),
            $this->constraintSetConstraints
        );

        $em = $this->getEntityManager();
        $em->transactional(function (EntityManagerInterface $em): void {
            $em->persist($this->constraintSet);
        });
    }

    /** @test */
    public function it_gets_existing_spaces(): void
    {
        $repository = $this->createRepository(2);
        $this->setUpSpace1($repository);
        $this->setUpSpace2($repository);

        $id = WebhostingSpaceId::fromString(self::SPACE_ID1);
        $id2 = WebhostingSpaceId::fromString(self::SPACE_ID2);
        $space = $repository->get($id);
        $space2 = $repository->get($id2);

        static::assertEquals($id, $space->getId());
        static::assertEquals(OwnerId::fromString(self::OWNER_ID1), $space->getOwner());
        static::assertEquals(new Constraints(), $space->getConstraints());
        static::assertNull($space->getAssignedConstraintSet());

        static::assertEquals($id2, $space2->getId());
        static::assertEquals(OwnerId::fromString(self::OWNER_ID1), $space2->getOwner());
        static::assertEquals($this->constraintSetConstraints, $space2->getConstraints());
        static::assertEquals($this->constraintSet, $space2->getAssignedConstraintSet());
    }

    /** @test */
    public function it_removes_an_existing_model(): void
    {
        $repository = $this->createRepository(3);
        $this->setUpSpace1($repository);
        $this->setUpSpace2($repository);

        $id = WebhostingSpaceId::fromString(self::SPACE_ID1);
        $space = $repository->get($id);

        // Mark for removal, then store this status.
        $space->markForRemoval();
        $repository->save($space);

        // Later another process will perform the removal operation
        $repository->remove($space);

        // Assert actually removed
        $this->expectException(WebhostingSpaceNotFound::class);
        $this->expectExceptionMessage(WebhostingSpaceNotFound::withId($id)->getMessage());
        $repository->get($id);
    }

    /** @test */
    public function it_checks_space_is_marked_for_removal(): void
    {
        $repository = $this->createRepository(1);
        $this->setUpSpace1($repository);

        $id = WebhostingSpaceId::fromString(self::SPACE_ID1);
        $space = $repository->get($id);

        $this->expectException(CannotRemoveActiveWebhostingSpace::class);
        $this->expectExceptionMessage(CannotRemoveActiveWebhostingSpace::withId($id)->getMessage());

        $repository->remove($space);
    }

    private function createRepository(int $expectedEventsCount): WebhostingSpaceOrmRepository
    {
        return new \ParkManager\Infrastructure\Doctrine\Repository\WebhostingSpaceOrmRepository($this->getEntityManager());
    }

    private function setUpSpace1(WebhostingSpaceOrmRepository $repository): void
    {
        $repository->save(
            Space::registerWithCustomConstraints(
                WebhostingSpaceId::fromString(self::SPACE_ID1),
                OwnerId::fromString(self::OWNER_ID1),
                new Constraints()
            )
        );
    }

    private function setUpSpace2(WebhostingSpaceOrmRepository $repository): void
    {
        $repository->save(
            Space::register(
                WebhostingSpaceId::fromString(self::SPACE_ID2),
                OwnerId::fromString(self::OWNER_ID1),
                $this->constraintSet
            )
        );
    }
}
