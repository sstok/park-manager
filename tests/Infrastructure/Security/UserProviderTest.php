<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Security;

use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Infrastructure\Security\SecurityUser;
use ParkManager\Infrastructure\Security\UserProvider;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @internal
 */
final class UserProviderTest extends TestCase
{
    private const USER_ID1 = '01dd5964-5426-11e7-be03-acbc32b58315';
    private const USER_ID2 = 'd398e0e4-b787-4647-b6ab-ab4bf2a2ed35';
    private const USER_ID3 = '6052d014-5ea4-40d7-8595-95356ff1c1ed';

    private const ADMIN_ID1 = 'a5b9cfec-e4a7-4c65-9fff-07752ec06b8e';

    /** @test */
    public function it_throws_fails_when_no_result_was_found(): void
    {
        $provider = new UserProvider(new UserRepositoryMock());

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername("admin\0foobar@example.com");
    }

    /** @test */
    public function it_throws_fails_when_no_result_was_found_for_refreshing(): void
    {
        $provider = new UserProvider(new UserRepositoryMock());

        $this->expectException(UsernameNotFoundException::class);

        $provider->refreshUser(new SecurityUser(self::USER_ID1, 'nope', true, ['ROLE_USER']));
    }

    /** @test */
    public function it_provides_a_security_user(): void
    {
        $provider = new UserProvider($this->createUserRepositoryStub());

        self::assertEquals(new SecurityUser(self::USER_ID1, 'maybe', true, ['ROLE_USER']), $provider->loadUserByUsername('foobar@example.com'));
        self::assertEquals(new SecurityUser(self::USER_ID2, 'maybe', true, ['ROLE_USER']), $provider->loadUserByUsername('bar@example.com'));
        self::assertEquals(new SecurityUser(self::ADMIN_ID1, 'nope3', true, ['ROLE_USER', 'ROLE_ADMIN']), $provider->loadUserByUsername('moo@example.com'));
    }

    private function createUserRepositoryStub(): UserRepositoryMock
    {
        return new UserRepositoryMock([
            User::register(UserId::fromString(self::USER_ID1), new EmailAddress('foobar@example.com'), 'He', 'maybe'),
            User::register(UserId::fromString(self::USER_ID2), new EmailAddress('bar@example.com'), 'He', 'maybe'),
            User::register(UserId::fromString(self::USER_ID3), new EmailAddress('foo@example.com'), 'He', 'nope2'),
            User::registerAdmin(UserId::fromString(self::ADMIN_ID1), new EmailAddress('moo@example.com'), 'He', 'nope3'),
        ]);
    }

    /** @test */
    public function it_refreshes_a_security_user(): void
    {
        $provider = new UserProvider($userRepo = $this->createUserRepositoryStub());
        $securityUser = $provider->loadUserByUsername('foobar@example.com');

        $user = $userRepo->get(UserId::fromString(self::USER_ID1));
        $user->changePassword('new-password-is-here');
        $userRepo->save($user);

        self::assertEquals(new SecurityUser(self::USER_ID1, 'new-password-is-here', true, ['ROLE_USER']), $provider->refreshUser($securityUser));
    }
}
