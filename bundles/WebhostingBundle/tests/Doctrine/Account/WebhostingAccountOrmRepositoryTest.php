<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Doctrine\Account;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Bundle\CoreBundle\Model\OwnerId;
use ParkManager\Bundle\CoreBundle\Test\Doctrine\EntityRepositoryTestCase;
use ParkManager\Bundle\CoreBundle\Test\Model\EventSourcedRepositoryTestHelper;
use ParkManager\Bundle\WebhostingBundle\Doctrine\Account\WebhostingAccountOrmRepository;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Exception\CannotRemoveActiveWebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Exception\WebhostingAccountNotFound;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuota;

/**
 * @internal
 *
 * @group functional
 */
final class WebhostingAccountOrmRepositoryTest extends EntityRepositoryTestCase
{
    use EventSourcedRepositoryTestHelper;

    private const OWNER_ID1 = '3f8da982-a528-11e7-a2da-acbc32b58315';
    private const PLAN_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';

    private const ACCOUNT_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const ACCOUNT_ID2 = '47f6db14-a69c-11e7-be13-acbc32b58315';

    /** @var Constraints */
    private $planConstraints;

    /** @var WebhostingPlan */
    private $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planConstraints = new Constraints(new MonthlyTrafficQuota(50));
        $this->plan = new WebhostingPlan(
            WebhostingPlanId::fromString(self::PLAN_ID1),
            $this->planConstraints
        );

        $em = $this->getEntityManager();
        $em->transactional(function (EntityManagerInterface $em): void {
            $em->persist($this->plan);
        });
    }

    /** @test */
    public function it_gets_existing_accounts(): void
    {
        $repository = $this->createRepository(2);
        $this->setUpAccount1($repository);
        $this->setUpAccount2($repository);

        $id = WebhostingAccountId::fromString(self::ACCOUNT_ID1);
        $id2 = WebhostingAccountId::fromString(self::ACCOUNT_ID2);
        $account = $repository->get($id);
        $account2 = $repository->get($id2);

        static::assertEquals($id, $account->getId());
        static::assertEquals(OwnerId::fromString(self::OWNER_ID1), $account->getOwner());
        static::assertEquals(new Constraints(), $account->getPlanConstraints());
        static::assertNull($account->getPlan());

        static::assertEquals($id2, $account2->getId());
        static::assertEquals(OwnerId::fromString(self::OWNER_ID1), $account2->getOwner());
        static::assertEquals($this->planConstraints, $account2->getPlanConstraints());
        static::assertEquals($this->plan, $account2->getPlan());
    }

    /** @test */
    public function it_removes_an_existing_model(): void
    {
        $repository = $this->createRepository(3);
        $this->setUpAccount1($repository);
        $this->setUpAccount2($repository);

        $id = WebhostingAccountId::fromString(self::ACCOUNT_ID1);
        $account = $repository->get($id);

        // Mark for removal, then store this status.
        $account->markForRemoval();
        $repository->save($account);

        // Later another process will perform the removal operation
        $repository->remove($account);

        // Assert actually removed
        $this->expectException(WebhostingAccountNotFound::class);
        $this->expectExceptionMessage(WebhostingAccountNotFound::withId($id)->getMessage());
        $repository->get($id);
    }

    /** @test */
    public function it_checks_account_is_marked_for_removal(): void
    {
        $repository = $this->createRepository(1);
        $this->setUpAccount1($repository);

        $id = WebhostingAccountId::fromString(self::ACCOUNT_ID1);
        $account = $repository->get($id);

        $this->expectException(CannotRemoveActiveWebhostingAccount::class);
        $this->expectExceptionMessage(CannotRemoveActiveWebhostingAccount::withId($id)->getMessage());

        $repository->remove($account);
    }

    private function createRepository(int $expectedEventsCount): WebhostingAccountOrmRepository
    {
        return new WebhostingAccountOrmRepository(
            $this->getEntityManager(),
            $this->createEventsExpectingEventBus()
        );
    }

    private function setUpAccount1(WebhostingAccountOrmRepository $repository): void
    {
        $repository->save(
            WebhostingAccount::registerWithCustomConstraints(
                WebhostingAccountId::fromString(self::ACCOUNT_ID1),
                OwnerId::fromString(self::OWNER_ID1),
                new Constraints()
            )
        );
    }

    private function setUpAccount2(WebhostingAccountOrmRepository $repository): void
    {
        $repository->save(
            WebhostingAccount::register(
                WebhostingAccountId::fromString(self::ACCOUNT_ID2),
                OwnerId::fromString(self::OWNER_ID1),
                $this->plan
            )
        );
    }
}
