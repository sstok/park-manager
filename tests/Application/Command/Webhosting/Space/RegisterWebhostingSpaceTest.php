<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Webhosting\Space;

use ParkManager\Application\Command\Webhosting\Space\RegisterWebhostingSpace;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RegisterWebhostingSpaceTest extends TestCase
{
    private const SPACE_ID = 'b288e23c-97c5-11e7-b51a-acbc32b58315';
    private const USER_ID = '2a9cd25c-97ca-11e7-9683-acbc32b58315';
    private const SET_ID = '654665ea-9869-11e7-9563-acbc32b58315';

    /** @test */
    public function its_constructable_with_constraint_set(): void
    {
        $command = RegisterWebhostingSpace::withPlan(
            self::SPACE_ID,
            $domainName = new DomainNamePair('example', 'com'),
            self::USER_ID,
            self::SET_ID
        );

        self::assertEquals(SpaceId::fromString(self::SPACE_ID), $command->id);
        self::assertEquals(OwnerId::fromString(self::USER_ID), $command->owner);
        self::assertEquals(PlanId::fromString(self::SET_ID), $command->planId);
        self::assertSame($domainName, $command->domainName);
        self::assertNull($command->customConstraints);
    }

    /** @test */
    public function its_constructable_with_custom_constraints(): void
    {
        $command = RegisterWebhostingSpace::withCustomConstraints(
            self::SPACE_ID,
            $domainName = new DomainNamePair('example', 'com'),
            self::USER_ID,
            $constraints = new Constraints()
        );

        self::assertEquals(SpaceId::fromString(self::SPACE_ID), $command->id);
        self::assertEquals(OwnerId::fromString(self::USER_ID), $command->owner);
        self::assertSame($constraints, $command->customConstraints);
        self::assertSame($domainName, $command->domainName);
        self::assertNull($command->planId);
    }
}
