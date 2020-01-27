<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Space;

use ParkManager\Application\Command\Webhosting\Space\RegisterWebhostingSpace;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceId;
use ParkManager\Domain\Webhosting\DomainName;
use ParkManager\Domain\Webhosting\Plan\Constraints;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlanId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RegisterWebhostingSpaceTest extends TestCase
{
    private const SPACE_ID = 'b288e23c-97c5-11e7-b51a-acbc32b58315';
    private const OWNER_ID = '2a9cd25c-97ca-11e7-9683-acbc32b58315';
    private const PLAN_ID = '654665ea-9869-11e7-9563-acbc32b58315';

    /** @test */
    public function its_constructable_with_plan(): void
    {
        $command = RegisterWebhostingSpace::withPlan(
            self::SPACE_ID,
            $domainName = new DomainName('example', 'com'),
            self::OWNER_ID,
            self::PLAN_ID
        );

        static::assertEquals(WebhostingSpaceId::fromString(self::SPACE_ID), $command->id);
        static::assertEquals(OwnerId::fromString(self::OWNER_ID), $command->owner);
        static::assertEquals(WebhostingPlanId::fromString(self::PLAN_ID), $command->plan);
        static::assertEquals($domainName, $command->domainName);
        static::assertNull($command->customConstraints);
    }

    /** @test */
    public function its_constructable_with_custom_constraints(): void
    {
        $command = RegisterWebhostingSpace::withCustomConstraints(
            self::SPACE_ID,
            $domainName = new DomainName('example', 'com'),
            self::OWNER_ID,
            $constraints = new Constraints(new MonthlyTrafficQuota(50))
        );

        static::assertEquals(WebhostingSpaceId::fromString(self::SPACE_ID), $command->id);
        static::assertEquals(OwnerId::fromString(self::OWNER_ID), $command->owner);
        static::assertEquals($constraints, $command->customConstraints);
        static::assertEquals($domainName, $command->domainName);
        static::assertNull($command->plan);
    }
}
