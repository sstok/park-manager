<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Owner;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\Exception\CannotRemoveActiveWebhostingSpace;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Doctrine\Repository\DomainNameOrmRepository;
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
    private Owner $owner1;

    private DomainNameOrmRepository $domainRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::register(
            UserId::fromString(self::OWNER_ID1),
            new EmailAddress('John@mustash.com'),
            'John',
            'ashTong@8r949029'
        );
        $this->owner1 = Owner::byUser($user);

        $this->constraints = (new Constraints())->setMonthlyTraffic(50);
        $this->plan = new Plan(
            PlanId::fromString(self::PLAN_ID1),
            $this->constraints
        );

        $domainName1 = DomainName::register(DomainNameId::fromString(self::SPACE_ID1), new DomainNamePair('example', 'com'), $this->owner1);
        $domainName2 = DomainName::register(DomainNameId::fromString(self::SPACE_ID2), new DomainNamePair('example', 'net'), $this->owner1);

        $em = $this->getEntityManager();

        $this->domainRepository = new DomainNameOrmRepository($em);

        $em->transactional(function (EntityManagerInterface $em) use ($user, $domainName1, $domainName2): void {
            $em->persist($user);
            $em->persist($this->owner1);
            $em->persist($this->plan);

            $this->domainRepository->save($domainName1);
            $this->domainRepository->save($domainName2);
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
        self::assertSame($this->owner1, $space->owner);
        self::assertEquals(new Constraints(), $space->constraints);
        self::assertNull($space->getAssignedPlan());

        self::assertEquals($id2, $space2->id);
        self::assertSame($this->owner1, $space2->owner);
        self::assertSame($this->constraints, $space2->constraints);
        self::assertSame($this->plan, $space2->getAssignedPlan());
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
            $space = Space::registerWithCustomConstraints(
                SpaceId::fromString(self::SPACE_ID1),
                $this->owner1,
                new Constraints()
            )
        );

        $domainName = $this->domainRepository->get(DomainNameId::fromString(self::SPACE_ID1));
        $domainName->transferToSpace($space, primary: true);

        $this->domainRepository->save($domainName);
        $repository->save($space);
    }

    private function setUpSpace2(WebhostingSpaceOrmRepository $repository): void
    {
        $repository->save(
            $space = Space::register(
                SpaceId::fromString(self::SPACE_ID2),
                $this->owner1,
                $this->plan
            )
        );

        $domainName = $this->domainRepository->get(DomainNameId::fromString(self::SPACE_ID2));
        $domainName->transferToSpace($space, primary: true);

        $this->domainRepository->save($domainName);
        $repository->save($space);
    }
}
