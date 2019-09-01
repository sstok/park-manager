<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Model\Account;

use DateTimeImmutable;
use ParkManager\Bundle\CoreBundle\Model\OwnerId;
use ParkManager\Bundle\CoreBundle\Test\Domain\EventsRecordingEntityAssertionTrait;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Event\WebhostingAccountCapabilitiesWasChanged;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Event\WebhostingAccountOwnerWasSwitched;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Event\WebhostingAccountPlanAssignmentWasChanged;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Event\WebhostingAccountWasMarkedForRemoval;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Event\WebhostingAccountWasRegistered;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Capabilities;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanCapability\MonthlyTrafficQuota;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingAccountTest extends TestCase
{
    use EventsRecordingEntityAssertionTrait;

    private const ACCOUNT_ID = '374dd50e-9b9f-11e7-9730-acbc32b58315';

    private const OWNER_ID1 = '2a9cd25c-97ca-11e7-9683-acbc32b58315';
    private const OWNER_ID2 = 'ce18c388-9ba2-11e7-b15f-acbc32b58315';

    private const PLAN_ID_1 = '654665ea-9869-11e7-9563-acbc32b58315';
    private const PLAN_ID_2 = 'f5788aae-9aed-11e7-a3c9-acbc32b58315';

    /** @test */
    public function it_registers_an_webhosting_account(): void
    {
        $id           = WebhostingAccountId::create();
        $capabilities = new Capabilities();
        $plan      = $this->createWebhostingPlan($capabilities);

        $account = WebhostingAccount::register($id, $owner = OwnerId::fromString(self::OWNER_ID1), $plan);

        self::assertEquals($id, $account->id());
        self::assertEquals($owner, $account->owner());
        self::assertSame($plan, $account->plan());
        self::assertSame($capabilities, $account->capabilities());
        self::assertDomainEvents($account, [new WebhostingAccountWasRegistered($id, $owner)]);
    }

    /** @test */
    public function it_registers_an_webhosting_account_with_custom_capabilities(): void
    {
        $id           = WebhostingAccountId::create();
        $capabilities = new Capabilities();

        $account = WebhostingAccount::registerWithCustomCapabilities($id, $owner = OwnerId::fromString(self::OWNER_ID1), $capabilities);

        self::assertEquals($id, $account->id());
        self::assertEquals($owner, $account->owner());
        self::assertSame($capabilities, $account->capabilities());
        self::assertNull($account->plan());
        self::assertDomainEvents($account, [new WebhostingAccountWasRegistered($id, $owner)]);
    }

    /** @test */
    public function it_allows_changing_plan_assignment(): void
    {
        $id2           = WebhostingAccountId::create();
        $capabilities1 = new Capabilities();
        $capabilities2 = new Capabilities(new MonthlyTrafficQuota(50));
        $plan1      = $this->createWebhostingPlan($capabilities1);
        $plan2      = $this->createWebhostingPlan($capabilities2, self::PLAN_ID_2);
        $account1      = WebhostingAccount::register(WebhostingAccountId::create(), OwnerId::fromString(self::OWNER_ID1), $plan1);
        $account2      = WebhostingAccount::register($id2, OwnerId::fromString(self::OWNER_ID1), $plan1);
        self::resetDomainEvents($account1, $account2);

        $account1->assignPlan($plan1);
        $account2->assignPlan($plan2);

        self::assertSame($plan1, $account1->plan(), 'Plan should not change');
        self::assertSame($plan1->capabilities(), $account1->capabilities(), 'Capabilities should not change');
        self::assertNoDomainEvents($account1);

        self::assertSame($plan2, $account2->plan());
        self::assertSame($plan1->capabilities(), $account2->capabilities());
        self::assertDomainEvents($account2, [new WebhostingAccountPlanAssignmentWasChanged($id2, $plan2)]);
    }

    /** @test */
    public function it_allows_changing_plan_assignment_with_capabilities(): void
    {
        $id2           = WebhostingAccountId::create();
        $capabilities1 = new Capabilities();
        $capabilities2 = new Capabilities(new MonthlyTrafficQuota(50));
        $plan1      = $this->createWebhostingPlan($capabilities1);
        $plan2      = $this->createWebhostingPlan($capabilities2, self::PLAN_ID_2);
        $account1      = WebhostingAccount::register(WebhostingAccountId::create(), OwnerId::fromString(self::OWNER_ID1), $plan1);
        $account2      = WebhostingAccount::register($id2, OwnerId::fromString(self::OWNER_ID1), $plan1);
        self::resetDomainEvents($account1, $account2);

        $account1->assignPlanWithCapabilities($plan1);
        $account2->assignPlanWithCapabilities($plan2);

        self::assertSame($plan1, $account1->plan(), 'Plan should not change');
        self::assertSame($plan1->capabilities(), $account1->capabilities(), 'Capabilities should not change');
        self::assertNoDomainEvents($account1);

        self::assertSame($plan2, $account2->plan());
        self::assertSame($plan2->capabilities(), $account2->capabilities());
        self::assertDomainEvents(
            $account2,
            [WebhostingAccountPlanAssignmentWasChanged::withCapabilities($id2, $plan2)]
        );
    }

    /** @test */
    public function it_updates_account_when_assigning_plan_capabilities_are_different(): void
    {
        $id      = WebhostingAccountId::create();
        $plan = $this->createWebhostingPlan(new Capabilities());
        $account = WebhostingAccount::register($id, OwnerId::fromString(self::OWNER_ID1), $plan);
        self::resetDomainEvents($account);

        $plan->changeCapabilities($newCapabilities = new Capabilities(new MonthlyTrafficQuota(50)));
        $account->assignPlanWithCapabilities($plan);

        self::assertSame($plan, $account->plan());
        self::assertSame($plan->capabilities(), $account->capabilities());
        self::assertDomainEvents(
            $account,
            [WebhostingAccountPlanAssignmentWasChanged::withCapabilities($id, $plan)]
        );
    }

    /** @test */
    public function it_allows_assigning_custom_specification(): void
    {
        $id      = WebhostingAccountId::create();
        $plan = $this->createWebhostingPlan(new Capabilities());
        $account = WebhostingAccount::register($id, OwnerId::fromString(self::OWNER_ID1), $plan);
        self::resetDomainEvents($account);

        $account->assignCustomCapabilities($newCapabilities = new Capabilities(new MonthlyTrafficQuota(50)));

        self::assertNull($account->plan());
        self::assertSame($newCapabilities, $account->capabilities());
        self::assertDomainEvents($account, [new WebhostingAccountCapabilitiesWasChanged($id, $newCapabilities)]);
    }

    /** @test */
    public function it_allows_changing_custom_specification(): void
    {
        $id      = WebhostingAccountId::create();
        $account = WebhostingAccount::registerWithCustomCapabilities($id, OwnerId::fromString(self::OWNER_ID1), new Capabilities());
        self::resetDomainEvents($account);

        $account->assignCustomCapabilities($newCapabilities = new Capabilities(new MonthlyTrafficQuota(50)));

        self::assertNull($account->plan());
        self::assertSame($newCapabilities, $account->capabilities());
        self::assertDomainEvents($account, [new WebhostingAccountCapabilitiesWasChanged($id, $newCapabilities)]);
    }

    /** @test */
    public function it_does_not_update_account_capabilities_when_assigning_capabilities_are_same(): void
    {
        $id           = WebhostingAccountId::create();
        $capabilities = new Capabilities();
        $account      = WebhostingAccount::registerWithCustomCapabilities($id, OwnerId::fromString(self::OWNER_ID1), $capabilities);
        self::resetDomainEvents($account);

        $account->assignCustomCapabilities($capabilities);

        self::assertNull($account->plan());
        self::assertSame($capabilities, $account->capabilities());
        self::assertNoDomainEvents($account);
    }

    /** @test */
    public function it_supports_switching_the_account_owner(): void
    {
        $account1 = WebhostingAccount::register(
            WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Capabilities())
        );
        $account2 = WebhostingAccount::register(
            $id2 = WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Capabilities())
        );
        self::resetDomainEvents($account1, $account2);

        $account1->switchOwner($owner1 = OwnerId::fromString(self::OWNER_ID1));
        $account2->switchOwner($owner2 = OwnerId::fromString(self::OWNER_ID2));

        self::assertEquals($owner1, $account1->owner());
        self::assertNoDomainEvents($account1);

        self::assertEquals($owner2, $account2->owner());
        self::assertDomainEvents($account2, [new WebhostingAccountOwnerWasSwitched($id2, $owner1, $owner2)]);
    }

    /** @test */
    public function it_allows_being_marked_for_removal(): void
    {
        $account1 = WebhostingAccount::register(
            WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Capabilities())
        );
        $account2 = WebhostingAccount::register(
            $id2 = WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Capabilities())
        );
        self::resetDomainEvents($account1, $account2);

        $account2->markForRemoval();
        $account2->markForRemoval();

        self::assertFalse($account1->isMarkedForRemoval());
        self::assertTrue($account2->isMarkedForRemoval());
        self::assertDomainEvents($account2, [new WebhostingAccountWasMarkedForRemoval($id2)]);
    }

    /** @test */
    public function it_can_expire(): void
    {
        $account1 = WebhostingAccount::register(
            WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Capabilities())
        );
        $account2 = WebhostingAccount::register(
            $id2 = WebhostingAccountId::fromString(self::ACCOUNT_ID),
            OwnerId::fromString(self::OWNER_ID1),
            $this->createWebhostingPlan(new Capabilities())
        );
        self::resetDomainEvents($account1, $account2);

        $account2->setExpirationDate($date = new DateTimeImmutable('now +6 days'));

        self::assertFalse($account1->isExpired());
        self::assertFalse($account1->isExpired($date->modify('+2 days')));

        self::assertFalse($account2->isExpired($date->modify('-10 days')));
        self::assertTrue($account2->isExpired($date));
        self::assertTrue($account2->isExpired($date->modify('+2 days')));

        $account1->removeExpirationDate();
        $account2->removeExpirationDate();

        self::assertFalse($account1->isExpired());
        self::assertFalse($account2->isExpired($date));
        self::assertFalse($account2->isExpired($date->modify('+2 days')));
    }

    private function createWebhostingPlan(Capabilities $capabilities, string $id = self::PLAN_ID_1): WebhostingPlan
    {
        return WebhostingPlan::create(WebhostingPlanId::fromString($id), $capabilities);
    }
}
