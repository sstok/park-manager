<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Administrator;

use Carbon\CarbonImmutable;
use ParkManager\Application\Command\Administrator\RegisterAdministrator;
use ParkManager\Application\Command\Administrator\RegisterAdministratorHandler;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\Exception\EmailAddressAlreadyInUse;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Tests\Mock\Domain\Organization\OrganizationRepositoryMock;
use ParkManager\Tests\Mock\Domain\OwnerRepositoryMock;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RegisterAdministratorHandlerTest extends TestCase
{
    private const ID_NEW = '01dd5964-5426-11e7-be03-acbc32b58315';
    private const ID_EXISTING = 'a0816f44-6545-11e7-a234-acbc32b58315';

    /** @after */
    public function unFreezeTime(): void
    {
        CarbonImmutable::setTestNow();
    }

    /** @test */
    public function handle_registration_of_new_administrator(): void
    {
        $repo = new UserRepositoryMock();
        $handler = new RegisterAdministratorHandler($repo, new OwnerRepositoryMock(), new OrganizationRepositoryMock($repo));

        $command = RegisterAdministrator::with(self::ID_NEW, 'John@example.com', 'My name', 'my-password');
        $handler($command);

        $repo->assertHasEntity(self::ID_NEW, static function (User $user): void {
            self::assertEquals(UserId::fromString(self::ID_NEW), $user->id);
            self::assertEquals(new EmailAddress('John@example.com'), $user->email);
            self::assertSame('My name', $user->displayName);
            self::assertSame('my-password', $user->password);
            self::assertNull($user->passwordExpiresOn);
            self::assertFalse($user->hasRole('ROLE_SUPER_ADMIN'));
        });
    }

    /** @test */
    public function handle_registration_of_new_administrator_with_new_password_requirement(): void
    {
        $repo = new UserRepositoryMock();
        $handler = new RegisterAdministratorHandler($repo, new OwnerRepositoryMock(), new OrganizationRepositoryMock($repo));

        $now = CarbonImmutable::parse('2021-01-04 15:06:00');
        CarbonImmutable::setTestNow($now);

        $command = RegisterAdministrator::with(self::ID_NEW, 'John@example.com', 'My name', 'my-password')
            ->requireNewPassword()
        ;
        $handler($command);

        $repo->assertHasEntity(self::ID_NEW, static function (User $user) use ($now): void {
            self::assertEquals(UserId::fromString(self::ID_NEW), $user->id);
            self::assertEquals(new EmailAddress('John@example.com'), $user->email);
            self::assertSame('My name', $user->displayName);
            self::assertSame('my-password', $user->password);
            self::assertNotNull($user->passwordExpiresOn);
            self::assertTrue($now->modify('-1 year')->equalTo($user->passwordExpiresOn));
            self::assertFalse($user->hasRole('ROLE_SUPER_ADMIN'));
        });
    }

    /** @test */
    public function handle_registration_of_new_super_administrator(): void
    {
        $repo = new UserRepositoryMock();
        $handler = new RegisterAdministratorHandler($repo, new OwnerRepositoryMock(), new OrganizationRepositoryMock($repo));

        $command = RegisterAdministrator::with(self::ID_NEW, 'John@example.com', 'My name', 'my-password')->asSuperAdmin();
        $handler($command);

        $repo->assertHasEntity(self::ID_NEW, static function (User $user): void {
            self::assertEquals(UserId::fromString(self::ID_NEW), $user->id);
            self::assertEquals(new EmailAddress('John@example.com'), $user->email);
            self::assertSame('My name', $user->displayName);
            self::assertSame('my-password', $user->password);
            self::assertTrue($user->hasRole('ROLE_SUPER_ADMIN'));
        });
    }

    /** @test */
    public function handle_registration_of_new_user_with_already_existing_email(): void
    {
        $repo = new UserRepositoryMock(
            [
                User::registerAdmin(
                    UserId::fromString(self::ID_EXISTING),
                    new EmailAddress('John@example.com'),
                    'Jane',
                    'Tucker@5423'
                ),
            ]
        );
        $handler = new RegisterAdministratorHandler($repo, new OwnerRepositoryMock(), new OrganizationRepositoryMock($repo));

        $this->expectException(EmailAddressAlreadyInUse::class);

        $handler(RegisterAdministrator::with(self::ID_NEW, 'John@example.com', 'My', 'null'));
    }
}
