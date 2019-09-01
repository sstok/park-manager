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
use ParkManager\Bundle\CoreBundle\Test\Domain\EventSourcedRepositoryTestHelper;
use ParkManager\Bundle\WebhostingBundle\Doctrine\Account\WebhostingAccountOrmRepository;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Exception\CannotRemoveActiveWebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Exception\WebhostingAccountNotFound;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Capabilities;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanCapability\MonthlyTrafficQuota;

/**
 * @internal
 *
 * @group functional
 */
final class WebhostingAccountOrmRepositoryTest extends EntityRepositoryTestCase
{
    use EventSourcedRepositoryTestHelper;

    private const OWNER_ID1   = '3f8da982-a528-11e7-a2da-acbc32b58315';
    private const PLAN_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';

    private const ACCOUNT_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const ACCOUNT_ID2 = '47f6db14-a69c-11e7-be13-acbc32b58315';

    /** @var Capabilities */
    private $planCapabilities;

    /** @var WebhostingPlan */
    private $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planCapabilities = new Capabilities(new MonthlyTrafficQuota(50));
        $this->plan             = WebhostingPlan::create(
            WebhostingPlanId::fromString(self::PLAN_ID1),
            $this->planCapabilities
        );

        $em = $this->getEntityManager();
        $em->transactional(function (EntityManagerInterface $em) {
            $em->persist($this->plan);
        });
    }

    /** @test */
    public function it_gets_existing_accounts(): void
    {
        $repository = $this->createRepository(2);
        $this->setUpAccount1($repository);
        $this->setUpAccount2($repository);

        $id       = WebhostingAccountId::fromString(self::ACCOUNT_ID1);
        $id2      = WebhostingAccountId::fromString(self::ACCOUNT_ID2);
        $account  = $repository->get($id);
        $account2 = $repository->get($id2);

        self::assertEquals($id, $account->id());
        self::assertEquals(OwnerId::fromString(self::OWNER_ID1), $account->owner());
        self::assertEquals(new Capabilities(), $account->capabilities());
        self::assertNull($account->plan());

        self::assertEquals($id2, $account2->id());
        self::assertEquals(OwnerId::fromString(self::OWNER_ID1), $account2->owner());
        self::assertEquals($this->planCapabilities, $account2->capabilities());
        self::assertEquals($this->plan, $account2->plan());
    }

    /** @test */
    public function it_removes_an_existing_model(): void
    {
        $repository = $this->createRepository(3);
        $this->setUpAccount1($repository);
        $this->setUpAccount2($repository);

        $id      = WebhostingAccountId::fromString(self::ACCOUNT_ID1);
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

        $id      = WebhostingAccountId::fromString(self::ACCOUNT_ID1);
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
            WebhostingAccount::registerWithCustomCapabilities(
                WebhostingAccountId::fromString(self::ACCOUNT_ID1),
                OwnerId::fromString(self::OWNER_ID1),
                new Capabilities()
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
