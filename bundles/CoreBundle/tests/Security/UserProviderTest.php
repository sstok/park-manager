<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\Security;

use InvalidArgumentException;
use ParkManager\Bundle\CoreBundle\Security\AdministratorUser;
use ParkManager\Bundle\CoreBundle\Security\AuthenticationFinder;
use ParkManager\Bundle\CoreBundle\Security\ClientUser;
use ParkManager\Bundle\CoreBundle\Security\SecurityUser;
use ParkManager\Bundle\CoreBundle\Security\UserProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @internal
 */
final class UserProviderTest extends TestCase
{
    /** @test */
    public function it_throws_fails_when_no_result_was_found(): void
    {
        $provider = new UserProvider($this->createNullFinderStub(), ClientUser::class);

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('foobar@example.com');
    }

    private function createNullFinderStub(): AuthenticationFinder
    {
        return new class() implements AuthenticationFinder {
            public function findAuthenticationByEmail(string $email): ?SecurityUser
            {
                return null;
            }

            public function findAuthenticationById(string $id): ?SecurityUser
            {
                return null;
            }
        };
    }

    /** @test */
    public function it_throws_fails_when_no_result_was_found_for_refreshing(): void
    {
        $provider = new UserProvider($this->createNullFinderStub(), ClientUser::class);

        $this->expectException(UsernameNotFoundException::class);

        $provider->refreshUser(new ClientUser('0', 'nope', true, []));
    }

    /** @test */
    public function it_checks_security_user_class_inheritance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected UserClass (stdClass) to be a child of');

        new UserProvider($this->createNullFinderStub(), stdClass::class);
    }

    /** @test */
    public function it_supports_only_a_configured_class(): void
    {
        $provider = new UserProvider($this->createNullFinderStub(), ClientUser::class);

        static::assertTrue($provider->supportsClass(ClientUser::class));
        static::assertFalse($provider->supportsClass(AdministratorUser::class));
    }

    /** @test */
    public function it_provides_a_security_user(): void
    {
        $provider = new UserProvider($this->createSingleUserFinderStub(), ClientUser::class);

        static::assertEquals(new ClientUser('1', 'maybe', true, ['ROLE_USER']), $provider->loadUserByUsername('foobar@example.com'));
        static::assertEquals(new ClientUser('2', '', true, ['ROLE_USER']), $provider->loadUserByUsername('bar@example.com'));
        static::assertEquals(new ClientUser('3', 'nope', false, ['ROLE_USER']), $provider->loadUserByUsername('foo@example.com'));
        static::assertEquals(new ClientUser('4', 'nope', true, ['ROLE_USER', 'ROLE_RESELLER']), $provider->loadUserByUsername('moo@example.com'));
    }

    /** @test */
    public function it_refreshes_a_security_user(): void
    {
        $provider = new UserProvider($this->createSingleUserFinderStub(), ClientUser::class);

        static::assertEquals(new ClientUser('1', '', true, ['ROLE_USER2']), $provider->refreshUser($provider->loadUserByUsername('foobar@example.com')));
        static::assertEquals(new ClientUser('2', 'maybe', false, ['ROLE_USER2']), $provider->refreshUser($provider->loadUserByUsername('bar@example.com')));
        static::assertEquals(new ClientUser('3', 'nope2', true, ['ROLE_USER2']), $provider->refreshUser($provider->loadUserByUsername('foo@example.com')));
        static::assertEquals(new ClientUser('4', 'nope2', true, ['ROLE_USER2', 'ROLE_RESELLER2']), $provider->refreshUser($provider->loadUserByUsername('moo@example.com')));
    }

    private function createSingleUserFinderStub(): AuthenticationFinder
    {
        return new class() implements AuthenticationFinder {
            public function findAuthenticationByEmail(string $email): ?SecurityUser
            {
                if ($email === 'foobar@example.com') {
                    return new ClientUser('1', 'maybe', true, ['ROLE_USER']);
                }

                if ($email === 'bar@example.com') {
                    return new ClientUser('2', '', true, ['ROLE_USER']);
                }

                if ($email === 'foo@example.com') {
                    return new ClientUser('3', 'nope', false, ['ROLE_USER']);
                }

                if ($email === 'moo@example.com') {
                    return new ClientUser('4', 'nope', true, ['ROLE_USER', 'ROLE_RESELLER']);
                }

                return null;
            }

            public function findAuthenticationById(string $id): ?SecurityUser
            {
                if ($id === '1') {
                    return new ClientUser('1', '', true, ['ROLE_USER2']);
                }

                if ($id === '2') {
                    return new ClientUser('2', 'maybe', false, ['ROLE_USER2']);
                }

                if ($id === '3') {
                    return new ClientUser('3', 'nope2', true, ['ROLE_USER2']);
                }

                if ($id === '4') {
                    return new ClientUser('4', 'nope2', true, ['ROLE_USER2', 'ROLE_RESELLER2']);
                }

                return null;
            }
        };
    }
}
