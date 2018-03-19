<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Core\Tests\Application\Administrator;

use ParkManager\Core\Application\Administrator\RegisterAdministrator;
use ParkManager\Core\Domain\Administrator\AdministratorId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RegisterAdministratorTest extends TestCase
{
    private const USER_ID = '45a8ce38-5405-11e7-8853-acbc32b58315';

    /** @test */
    public function its_constructable()
    {
        $command = new RegisterAdministrator(self::USER_ID, 'John@example.com', 'First', 'Last', 'empty');

        self::assertEquals(AdministratorId::fromString(self::USER_ID), $command->id());
        self::assertEquals('John@example.com', $command->email());
        self::assertEquals('First', $command->firstName());
        self::assertEquals('Last', $command->lastName());
        self::assertEquals('empty', $command->password());
    }

    /** @test */
    public function its_password_is_optional()
    {
        $command = new RegisterAdministrator(self::USER_ID, 'John@example.com', 'First', 'Last');

        self::assertEquals(AdministratorId::fromString(self::USER_ID), $command->id());
        self::assertEquals('John@example.com', $command->email());
        self::assertEquals('First', $command->firstName());
        self::assertEquals('Last', $command->lastName());
        self::assertNull($command->password());
    }
}
