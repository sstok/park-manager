<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Administrator;

use ParkManager\Domain\Administrator\Administrator;
use ParkManager\Domain\Administrator\AdministratorId;
use ParkManager\Domain\Administrator\Exception\AdministratorEmailAddressAlreadyInUse;
use ParkManager\Domain\EmailAddress;
use ParkManager\Tests\Mock\Domain\AdministratorRepositoryMock;
use ParkManager\Application\Command\Administrator\RegisterAdministrator;
use ParkManager\Application\Command\Administrator\RegisterAdministratorHandler;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RegisterAdministratorHandlerTest extends TestCase
{
    private const ID_NEW = '01dd5964-5426-11e7-be03-acbc32b58315';
    private const ID_EXISTING = 'a0816f44-6545-11e7-a234-acbc32b58315';

    /** @test */
    public function handle_registration_of_new_administrator(): void
    {
        $repo = new AdministratorRepositoryMock();
        $handler = new RegisterAdministratorHandler($repo);

        $command = new RegisterAdministrator(self::ID_NEW, 'John@example.com', 'My name', 'my-password');
        $handler($command);

        $repo->assertHasEntity(self::ID_NEW, static function (Administrator $administrator): void {
            self::assertEquals(AdministratorId::fromString(self::ID_NEW), $administrator->getId());
            self::assertEquals(new EmailAddress('John@example.com'), $administrator->getEmailAddress());
            self::assertEquals('My name', $administrator->getDisplayName());
            self::assertEquals('my-password', $administrator->getPassword());
        });
    }

    /** @test */
    public function handle_registration_of_new_user_with_already_existing_email(): void
    {
        $repo = new AdministratorRepositoryMock(
            [
                Administrator::register(
                    AdministratorId::fromString(self::ID_EXISTING),
                    new EmailAddress('John@example.com'),
                    'Jane'
                ),
            ]
        );
        $handler = new RegisterAdministratorHandler($repo);

        $this->expectException(AdministratorEmailAddressAlreadyInUse::class);

        $handler(new RegisterAdministrator(self::ID_NEW, 'John@example.com', 'My', null));
    }
}
