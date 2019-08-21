<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\UseCase\Client;

use ParkManager\Bundle\CoreBundle\Model\Client\ClientId;
use ParkManager\Bundle\CoreBundle\UseCase\Client\ChangeClientPassword;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ChangeClientPasswordTest extends TestCase
{
    private const USER_ID = '45a8ce38-5405-11e7-8853-acbc32b58315';

    /** @test */
    public function its_constructable(): void
    {
        $command = new ChangeClientPassword($id = self::USER_ID, 'empty');

        self::assertEquals(ClientId::fromString(self::USER_ID), $command->id());
        self::assertEquals('empty', $command->password());
    }

    /** @test */
    public function its_password_can_be_null(): void
    {
        $command = new ChangeClientPassword($id = self::USER_ID, null);

        self::assertEquals(ClientId::fromString(self::USER_ID), $command->id());
        self::assertNull($command->password());
    }
}
