<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Space;

use ParkManager\Application\Command\Webhosting\Space\RegisterWebhostingSpace;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\ConstraintSetId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
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
        $command = RegisterWebhostingSpace::withConstraintSet(
            self::SPACE_ID,
            $domainName = new DomainNamePair('example', 'com'),
            self::USER_ID,
            self::SET_ID
        );

        self::assertEquals(SpaceId::fromString(self::SPACE_ID), $command->id);
        self::assertEquals(UserId::fromString(self::USER_ID), $command->owner);
        self::assertEquals(ConstraintSetId::fromString(self::SET_ID), $command->constraintSetId);
        self::assertEquals($domainName, $command->domainName);
        self::assertNull($command->customConstraints);
    }

    /** @test */
    public function its_constructable_with_custom_constraints(): void
    {
        $command = RegisterWebhostingSpace::withCustomConstraints(
            self::SPACE_ID,
            $domainName = new DomainNamePair('example', 'com'),
            self::USER_ID,
            $constraints = new Constraints(new MonthlyTrafficQuota(50))
        );

        self::assertEquals(SpaceId::fromString(self::SPACE_ID), $command->id);
        self::assertEquals(UserId::fromString(self::USER_ID), $command->owner);
        self::assertEquals($constraints, $command->customConstraints);
        self::assertEquals($domainName, $command->domainName);
        self::assertNull($command->constraintSetId);
    }
}
