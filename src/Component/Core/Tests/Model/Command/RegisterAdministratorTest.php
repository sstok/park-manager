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

namespace ParkManager\Component\Core\Tests\Model\Command;

use ParkManager\Component\Core\Model\Command\RegisterAdministrator;
use ParkManager\Component\Model\Test\DomainMessageAssertion;
use ParkManager\Component\User\Model\UserId;
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

        self::assertEquals(UserId::fromString(self::USER_ID), $command->id());
        self::assertEquals('John@example.com', $command->email());
        self::assertEquals('First', $command->firstName());
        self::assertEquals('Last', $command->lastName());
        self::assertEquals('empty', $command->password());

        DomainMessageAssertion::assertGettersEqualAfterEncoding($command);
    }

    /** @test */
    public function its_password_is_optional()
    {
        $command = new RegisterAdministrator(self::USER_ID, 'John@example.com', 'First', 'Last');

        self::assertEquals(UserId::fromString(self::USER_ID), $command->id());
        self::assertEquals('John@example.com', $command->email());
        self::assertEquals('First', $command->firstName());
        self::assertEquals('Last', $command->lastName());
        self::assertNull($command->password());

        DomainMessageAssertion::assertGettersEqualAfterEncoding($command);
    }
}
