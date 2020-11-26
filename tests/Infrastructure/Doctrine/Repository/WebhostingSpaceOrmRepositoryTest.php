<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\Exception\CannotRemoveActiveWebhostingSpace;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
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
    private const PLAN_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';

    private const SPACE_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const SPACE_ID2 = '47f6db14-a69c-11e7-be13-acbc32b58315';

    private Constraints $constraints;
    private Plan $plan;
    private User $user1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::register(UserId::fromString(self::OWNER_ID1), new EmailAddress('John@mustash.com'), 'John');

        $this->constraints = (new Constraints())->setMonthlyTraffic(50);
        $this->plan = new Plan(
            PlanId::fromString(self::PLAN_ID1),
            $this->constraints
        );

        $em = $this->getEntityManager();
        $em->transactional(function (EntityManagerInterface $em): void {
            $em->persist($this->user1);
            $em->persist($this->plan);
        });
    }

    /** @test */
    public function it_gets_existing_spaces(): void
    {
        $repository = new WebhostingSpaceOrmRepository($this->getEntityManager());
        $this->setUpSpace1($repository);
        $this->setUpSpace2($repository);

        $id = SpaceId::fromString(self::SPACE_ID1);
        $id2 = SpaceId::fromString(self::SPACE_ID2);
        $space = $repository->get($id);
        $space2 = $repository->get($id2);

        self::assertEquals($id, $space->id);
        self::assertEquals($this->user1, $space->owner);
        self::assertEquals(new Constraints(), $space->constraints);
        self::assertNull($space->getAssignedPlan());

        self::assertEquals($id2, $space2->id);
        self::assertEquals($this->user1, $space2->owner);
        self::assertEquals($this->constraints, $space2->constraints);
        self::assertEquals($this->plan, $space2->getAssignedPlan());
    }

    /** @test */
    public function it_gets_all_with_assigned_plan(): void
    {
        $repository = new WebhostingSpaceOrmRepository($this->getEntityManager());
        $this->setUpSpace1($repository);
        $this->setUpSpace2($repository);

        $em = $this->getEntityManager();
        $em->flush();
        $em->clear();

        $this->assertIdsEquals([self::SPACE_ID2], $repository->allWithAssignedPlan(PlanId::fromString(self::PLAN_ID1)));
    }

    /** @test */
    public function it_removes_an_existing_model(): void
    {
        $repository = new WebhostingSpaceOrmRepository($this->getEntityManager());
        $this->setUpSpace1($repository);
        $this->setUpSpace2($repository);

        $id = SpaceId::fromString(self::SPACE_ID1);
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
        $repository = new WebhostingSpaceOrmRepository($this->getEntityManager());
        $this->setUpSpace1($repository);

        $id = SpaceId::fromString(self::SPACE_ID1);
        $space = $repository->get($id);

        $this->expectException(CannotRemoveActiveWebhostingSpace::class);
        $this->expectExceptionMessage(CannotRemoveActiveWebhostingSpace::withId($id)->getMessage());

        $repository->remove($space);
    }

    private function setUpSpace1(WebhostingSpaceOrmRepository $repository): void
    {
        $repository->save(
            Space::registerWithCustomConstraints(
                SpaceId::fromString(self::SPACE_ID1),
                $this->user1,
                new Constraints()
            )
        );
    }

    private function setUpSpace2(WebhostingSpaceOrmRepository $repository): void
    {
        $repository->save(
            Space::register(
                SpaceId::fromString(self::SPACE_ID2),
                $this->user1,
                $this->plan
            )
        );
    }
}
