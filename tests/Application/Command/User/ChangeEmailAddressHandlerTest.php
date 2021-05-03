<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\User;

use ParkManager\Application\Command\User\ChangeEmailAddress;
use ParkManager\Application\Command\User\ChangeEmailAddressHandler;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\Exception\EmailAddressAlreadyInUse;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ChangeEmailAddressHandlerTest extends TestCase
{
    public const ID2 = 'fcb8322b-158d-4581-a310-e282178d4dc9';
    private const ID1 = 'dff0ce42-de6a-4e92-9fab-21809024aa00';

    private ChangeEmailAddressHandler $handler;
    private UserRepositoryMock $repository;

    protected function setUp(): void
    {
        $this->repository = new UserRepositoryMock([
            UserRepositoryMock::createUser('jimy@example.com', self::ID1),
            UserRepositoryMock::createUser('jimy-t@example.com', self::ID2),
        ]);
        $this->handler = new ChangeEmailAddressHandler($this->repository);
    }

    /** @test */
    public function it_does_nothing_when_email_is_unchanged(): void
    {
        ($this->handler)(new ChangeEmailAddress(UserId::fromString(self::ID1), new EmailAddress('jimy@example.com')));

        $this->repository->assertNoEntitiesWereSaved();
    }

    /** @test */
    public function it_rejects_address_is_already_in_use(): void
    {
        $this->expectExceptionObject(
            new EmailAddressAlreadyInUse(
                UserId::fromString(self::ID2),
                new EmailAddress('jimy-t@example.com')
            )
        );

        ($this->handler)(new ChangeEmailAddress(UserId::fromString(self::ID1), new EmailAddress('jimy-t@example.com')));
    }

    /** @test */
    public function it_saves_when_address_is_different(): void
    {
        ($this->handler)(new ChangeEmailAddress(UserId::fromString(self::ID1), new EmailAddress('jimy2@example.com')));

        $this->repository->assertEntityWasSavedThat(self::ID1, static function (User $user) {
            return $user->email->equals(new EmailAddress('jimy2@example.com'));
        });
    }

    /** @test */
    public function it_saves_when_address_label_is_different(): void
    {
        ($this->handler)(new ChangeEmailAddress(UserId::fromString(self::ID1), new EmailAddress('jimy+spam@example.com')));

        $this->repository->assertEntityWasSavedThat(self::ID1, static function (User $user) {
            return $user->email->equals(new EmailAddress('jimy+spam@example.com'));
        });
    }
}
