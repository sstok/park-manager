<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\UseCase\Account;

use ParkManager\Bundle\CoreBundle\Model\OwnerId;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuota;
use ParkManager\Bundle\WebhostingBundle\UseCase\Account\RegisterWebhostingAccount;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RegisterWebhostingAccountTest extends TestCase
{
    private const ACCOUNT_ID = 'b288e23c-97c5-11e7-b51a-acbc32b58315';
    private const OWNER_ID = '2a9cd25c-97ca-11e7-9683-acbc32b58315';
    private const PLAN_ID = '654665ea-9869-11e7-9563-acbc32b58315';

    /** @test */
    public function its_constructable_with_plan(): void
    {
        $command = RegisterWebhostingAccount::withPlan(
            self::ACCOUNT_ID,
            $domainName = new DomainName('example', 'com'),
            self::OWNER_ID,
            self::PLAN_ID
        );

        static::assertEquals(WebhostingAccountId::fromString(self::ACCOUNT_ID), $command->id);
        static::assertEquals(OwnerId::fromString(self::OWNER_ID), $command->owner);
        static::assertEquals(WebhostingPlanId::fromString(self::PLAN_ID), $command->plan);
        static::assertEquals($domainName, $command->domainName);
        static::assertNull($command->customConstraints);
    }

    /** @test */
    public function its_constructable_with_custom_constraints(): void
    {
        $command = RegisterWebhostingAccount::withCustomConstraints(
            self::ACCOUNT_ID,
            $domainName = new DomainName('example', 'com'),
            self::OWNER_ID,
            $constraints = new Constraints(new MonthlyTrafficQuota(50))
        );

        static::assertEquals(WebhostingAccountId::fromString(self::ACCOUNT_ID), $command->id);
        static::assertEquals(OwnerId::fromString(self::OWNER_ID), $command->owner);
        static::assertEquals($constraints, $command->customConstraints);
        static::assertEquals($domainName, $command->domainName);
        static::assertNull($command->plan);
    }
}
