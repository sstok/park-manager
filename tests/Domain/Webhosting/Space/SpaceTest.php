<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Space;

use Assert\Assertion;
use Assert\InvalidArgumentException;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Owner;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\AccessSuspensionLog;
use ParkManager\Domain\Webhosting\Space\Exception\CannotTransferSystemWebhostingSpace;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\SpaceSetupStatus;
use ParkManager\Domain\Webhosting\Space\SuspensionLevel;
use ParkManager\Tests\Mock\Domain\Organization\OrganizationRepositoryMock;
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

    /**
     * @after
     */
    public function resetTestTime(): void
    {
        CarbonImmutable::setTestNow(false);
    }

    /** @test */
    public function it_registers_an_webhosting_space(): void
    {
        $id = SpaceId::create();
        $constraints = new Constraints();
        $plan = $this->createPlan($constraints);
        $owner = Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1));

        $space = Space::register($id, $owner, $plan);

        self::assertSame($id, $space->id);
        self::assertSame($owner, $space->owner);
        self::assertSame($plan, $space->plan);
        self::assertSame($constraints, $space->constraints);
    }

    private function createPlan(Constraints $constraints, string $id = self::SET_ID_1): Plan
    {
        return new Plan(PlanId::fromString($id), $constraints);
    }

    /** @test */
    public function it_registers_an_webhosting_space_with_custom_constraints(): void
    {
        $owner = Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1));
        $id = SpaceId::create();
        $constraints = new Constraints();

        $space = Space::registerWithCustomConstraints($id, $owner, $constraints);

        self::assertSame($id, $space->id);
        self::assertSame($owner, $space->owner);
        self::assertSame($constraints, $space->constraints);
        self::assertNull($space->plan);
    }

    /** @test */
    public function it_allows_changing_constraint_set_assignment(): void
    {
        $owner = Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1));
        $constraints1 = new Constraints();
        $constraints2 = (new Constraints())->setMonthlyTraffic(50);
        $plan1 = $this->createPlan($constraints1);
        $plan2 = $this->createPlan($constraints2, self::SET_ID_2);
        $space1 = Space::register(SpaceId::create(), $owner, $plan1);
        $space2 = Space::register(SpaceId::create(), $owner, $plan1);

        $space1->assignPlan($plan1);
        $space2->assignPlan($plan2);

        self::assertSame($plan1, $space1->plan, 'Plan should not change');
        self::assertSame($plan1->constraints, $space1->constraints, 'Constraints should not change');

        self::assertSame($plan2, $space2->plan);
        self::assertSame($plan1->constraints, $space2->constraints);
    }

    /** @test */
    public function it_allows_changing_constraint_set_assignment_with_constraints(): void
    {
        $owner = Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1));
        $constraints1 = new Constraints();
        $constraints2 = (new Constraints())->setMonthlyTraffic(50);
        $plan1 = $this->createPlan($constraints1);
        $plan2 = $this->createPlan($constraints2, self::SET_ID_2);
        $space1 = Space::register(SpaceId::create(), $owner, $plan1);
        $space2 = Space::register(SpaceId::create(), $owner, $plan1);

        $space1->assignPlanWithConstraints($plan1, $plan1->constraints);
        $space2->assignPlanWithConstraints($plan2, $plan2->constraints);

        self::assertSame($plan1, $space1->plan, 'Plan should not change');
        self::assertSame($plan1->constraints, $space1->constraints, 'Constraints should not change');

        self::assertSame($plan2, $space2->plan);
        self::assertSame($plan2->constraints, $space2->constraints);
    }

    /** @test */
    public function it_updates_space_when_assigning_constraint_set_constraints_are_different(): void
    {
        $plan = $this->createPlan(new Constraints());
        $space = Space::register(
            SpaceId::create(),
            Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1)),
            $plan
        );

        $plan->changeConstraints((new Constraints())->setMonthlyTraffic(50));
        $space->assignPlanWithConstraints($plan, $plan->constraints);

        self::assertSame($plan, $space->plan);
        self::assertSame($plan->constraints, $space->constraints);
    }

    /** @test */
    public function it_allows_assigning_custom_specification(): void
    {
        $plan = $this->createPlan(new Constraints());
        $space = Space::register(
            SpaceId::create(),
            Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1)),
            $plan
        );

        $space->assignCustomConstraints($newConstraints = (new Constraints())->setMonthlyTraffic(50));

        self::assertNull($space->plan);
        self::assertSame($newConstraints, $space->constraints);
    }

    /** @test */
    public function it_allows_changing_custom_specification(): void
    {
        $space = Space::registerWithCustomConstraints(
            SpaceId::create(),
            Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1)),
            new Constraints()
        );

        $space->assignCustomConstraints($newConstraints = (new Constraints())->setMonthlyTraffic(50));

        self::assertNull($space->plan);
        self::assertSame($newConstraints, $space->constraints);
    }

    /** @test */
    public function it_does_not_update_space_constraints_when_assigning_constraints_are_same(): void
    {
        $constraints = new Constraints();
        $space = Space::registerWithCustomConstraints(
            SpaceId::create(),
            Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1)),
            $constraints
        );

        $space->assignCustomConstraints($constraints);

        self::assertNull($space->plan);
        self::assertSame($constraints, $space->constraints);
    }

    /** @test */
    public function it_allows_setting_web_storage_quota(): void
    {
        $constraints = new Constraints();
        $space = Space::registerWithCustomConstraints(
            SpaceId::create(),
            Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1)),
            $constraints
        );

        self::assertNull($space->webQuota);

        $space->setWebQuota($size = new ByteSize(12, 'GB'));
        self::assertSame($size, $space->webQuota);

        // Not changed.
        $space->setWebQuota($size2 = new ByteSize(12, 'GB'));
        self::assertNotSame($size2, $space->webQuota);
        self::assertSame($size, $space->webQuota);
    }

    /** @test */
    public function it_requires_web_quota_does_not_exceed_storage_size(): void
    {
        $constraints = new Constraints(['storageSize' => $size = new ByteSize(12, 'GB')]);
        $space = Space::registerWithCustomConstraints(
            SpaceId::create(),
            Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1)),
            $constraints
        );

        $this->expectExceptionObject(new InvalidArgumentException('WebSpace quota cannot be greater than the total storage size.', Assertion::INVALID_FALSE));

        $space->setWebQuota($size = new ByteSize(13, 'GB'));
    }

    /** @test */
    public function it_supports_switching_the_space_owner(): void
    {
        $owner1 = Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1));
        $owner2 = Owner::byUser(UserRepositoryMock::createUser('joHn@example.com', self::OWNER_ID2));
        $space1 = Space::register(
            SpaceId::fromString(self::SPACE_ID),
            $owner1,
            $this->createPlan(new Constraints())
        );
        $space2 = Space::register(
            $id2 = SpaceId::fromString(self::SPACE_ID),
            $owner1,
            $this->createPlan(new Constraints())
        );

        $space1->transferToOwner($owner1);
        $space2->transferToOwner($owner2);

        self::assertSame($owner1, $space1->owner);
        self::assertSame($owner2, $space2->owner);
    }

    /** @test */
    public function it_forbids_transferring_from_system_owner(): void
    {
        $userRepository = new UserRepositoryMock([UserRepositoryMock::createUser('joHn@example.com', self::OWNER_ID2)]);
        $organizationRepository = new OrganizationRepositoryMock($userRepository);

        $systemOwner = Owner::byOrganization($organizationRepository->get(OrganizationId::fromString(OrganizationId::SYSTEM_APP)));
        $userOwner = Owner::byUser($userRepository::createUser('joHn@example.com', self::OWNER_ID2));

        $space = Space::register(
            $id = SpaceId::fromString(self::SPACE_ID),
            $systemOwner,
            $this->createPlan(new Constraints())
        );

        $this->expectExceptionObject(CannotTransferSystemWebhostingSpace::withId($id));

        $space->transferToOwner($userOwner);
    }

    /** @test */
    public function it_allows_being_marked_for_removal(): void
    {
        $owner = Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1));
        $space1 = Space::register(
            SpaceId::fromString(self::SPACE_ID),
            $owner,
            $this->createPlan(new Constraints())
        );
        $space2 = Space::register(
            $id2 = SpaceId::fromString(self::SPACE_ID),
            $owner,
            $this->createPlan(new Constraints())
        );

        $space2->markForRemoval();
        $space2->markForRemoval();

        self::assertFalse($space1->isMarkedForRemoval());
        self::assertTrue($space2->isMarkedForRemoval());
    }

    /** @test */
    public function it_can_expire(): void
    {
        $owner = Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1));
        $space1 = Space::register(
            SpaceId::fromString(self::SPACE_ID),
            $owner,
            $this->createPlan(new Constraints())
        );
        $space2 = Space::register(
            $id2 = SpaceId::fromString(self::SPACE_ID),
            $owner,
            $this->createPlan(new Constraints())
        );

        $space2->markExpirationDate($date = new DateTimeImmutable('now +6 days'));

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

    /** @test */
    public function it_allows_access_to_be_suspended(): void
    {
        $space = Space::register(
            SpaceId::fromString(self::SPACE_ID),
            Owner::byUser(UserRepositoryMock::createUser('janE@example.com', self::OWNER_ID1)),
            $this->createPlan(new Constraints())
        );
        $space->assignSetupStatus(SpaceSetupStatus::from(SpaceSetupStatus::GETTING_INITIALIZED));
        $space->assignSetupStatus(SpaceSetupStatus::from(SpaceSetupStatus::READY));

        self::assertCount(0, $space->getSuspensions());

        CarbonImmutable::setTestNow('2021-05-02T14:12:14.000000+0000');

        $space->suspendAccess(SuspensionLevel::get('ACCESS_RESTRICTED'));
        $space->suspendAccess(SuspensionLevel::get('LOCKED'));

        CarbonImmutable::setTestNow('2021-06-02T14:12:14.000000+0000');

        $space->suspendAccess(SuspensionLevel::get('LOCKED')); // Should not be logged
        $space->suspendAccess(SuspensionLevel::get('ACCESS_RESTRICTED'));
        $space->removeAccessSuspension();
        $space->removeAccessSuspension();

        self::assertEquals(
            [
                new AccessSuspensionLog($space, SuspensionLevel::get('ACCESS_RESTRICTED'), new CarbonImmutable('2021-05-02T14:12:14.000000+0000')),
                new AccessSuspensionLog($space, SuspensionLevel::get('LOCKED'), new CarbonImmutable('2021-05-02T14:12:14.000000+0000')),
                new AccessSuspensionLog($space, SuspensionLevel::get('ACCESS_RESTRICTED'), new CarbonImmutable('2021-06-02T14:12:14.000000+0000')),
                new AccessSuspensionLog($space, null, new CarbonImmutable('2021-06-02T14:12:14.000000+0000')),
            ],
            $space->getSuspensions()->toArray()
        );
    }
}
