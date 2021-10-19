<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Security;

use Carbon\CarbonImmutable;
use ParkManager\Application\Command\User\ChangePassword;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\MalformedEmailAddress;
use ParkManager\Domain\Exception\NotFoundException;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Infrastructure\Security\SecurityUser;
use ParkManager\Infrastructure\Security\UserProvider;
use ParkManager\Tests\Mock\Application\Service\SpyingMessageBus;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

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
    public function it_only_supports_security_user(): void
    {
        $provider = new UserProvider(new UserRepositoryMock(), new SpyingMessageBus());

        self::assertTrue($provider->supportsClass(SecurityUser::class));
        self::assertFalse($provider->supportsClass('SecurityUser'));
        self::assertFalse($provider->supportsClass('stdClass'));
    }

    /** @test */
    public function it_fails_when_no_result_was_found(): void
    {
        $provider = new UserProvider(new UserRepositoryMock(), new SpyingMessageBus());

        try {
            $provider->loadUserByIdentifier('foobar@example.com');

            self::fail('Expected exception');
        } catch (UserNotFoundException $e) {
            self::assertSame('foobar@example.com', $e->getUserIdentifier());
            self::assertInstanceOf(NotFoundException::class, $e->getPrevious());
            self::assertSame(0, $e->getCode());
        }
    }

    /** @test */
    public function it_fails_when_email_address_is_invalid(): void
    {
        $provider = new UserProvider(new UserRepositoryMock(), new SpyingMessageBus());

        try {
            $provider->loadUserByIdentifier('foobar@');

            self::fail('Expected exception');
        } catch (UserNotFoundException $e) {
            self::assertSame('foobar@', $e->getUserIdentifier());
            self::assertInstanceOf(MalformedEmailAddress::class, $e->getPrevious());
            self::assertSame(0, $e->getCode());
        }
    }

    /** @test */
    public function it_throws_fails_when_no_result_was_found_for_refreshing(): void
    {
        $provider = new UserProvider(new UserRepositoryMock(), new SpyingMessageBus());

        try {
            $provider->refreshUser(new SecurityUser(self::USER_ID1, 'nope', true, ['ROLE_USER']));

            self::fail('Expected exception');
        } catch (UserNotFoundException $e) {
            self::assertSame(self::USER_ID1, $e->getUserIdentifier());
            self::assertInstanceOf(NotFoundException::class, $e->getPrevious());
            self::assertSame(0, $e->getCode());
        }
    }

    /** @test */
    public function it_throws_fails_when_unsupported_user_was_provided_for_refreshing(): void
    {
        $provider = new UserProvider(new UserRepositoryMock(), new SpyingMessageBus());

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Expected an instance of %s, but got ', SecurityUser::class));

        $provider->refreshUser($this->createMock(UserInterface::class));
    }

    /** @test */
    public function it_provides_a_security_user(): void
    {
        $provider = new UserProvider($this->createUserRepositoryStub(), new SpyingMessageBus());

        self::assertEquals(new SecurityUser(self::USER_ID1, 'maybe', true, ['ROLE_USER']), $provider->loadUserByIdentifier('foobar@example.com'));
        self::assertEquals(new SecurityUser(self::USER_ID2, 'maybe', true, ['ROLE_USER']), $provider->loadUserByIdentifier('bar@example.com'));
        self::assertEquals(new SecurityUser(self::ADMIN_ID1, 'nope3', true, ['ROLE_USER', 'ROLE_ADMIN']), $provider->loadUserByIdentifier('moo@example.com'));
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
        $provider = new UserProvider($userRepo = $this->createUserRepositoryStub(), new SpyingMessageBus());
        $securityUser = $provider->loadUserByIdentifier('foobar@example.com');

        $user = $userRepo->get(UserId::fromString(self::USER_ID1));
        $user->changePassword('new-password-is-here');
        $userRepo->save($user);

        self::assertEquals(new SecurityUser(self::USER_ID1, 'new-password-is-here', true, ['ROLE_USER']), $provider->refreshUser($securityUser));
    }

    /** @test */
    public function it_upgrades_password(): void
    {
        $messageBus = new SpyingMessageBus();

        $provider = new UserProvider($this->createUserRepositoryStub(), $messageBus);
        $securityUser = $provider->loadUserByIdentifier('foobar@example.com');

        $provider->upgradePassword($securityUser, $newHashedPassword = 'look-@-me.I am the new password now');

        self::assertEquals([new ChangePassword($securityUser->getId(), $newHashedPassword)], $messageBus->dispatchedMessages);
    }

    /** @test */
    public function it_rejects_password_upgrade_when_password_is_expired(): void
    {
        $userRepository = $this->createUserRepositoryStub();
        $user = $userRepository->get(UserId::fromString(self::USER_ID1));
        $user->expirePasswordOn(new CarbonImmutable('2 days ago'));
        $userRepository->save($user);

        $messageBus = new SpyingMessageBus();
        $provider = new UserProvider($userRepository, $messageBus);
        $securityUser = $provider->loadUserByIdentifier('foobar@example.com');

        $provider->upgradePassword($securityUser, 'look-@-me.I am the new password now');

        self::assertEquals([], $messageBus->dispatchedMessages);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function it_continues_gracefully_when_upgrade_fails(): void
    {
        // No handler registered.
        $messageBus = new MessageBus([new HandleMessageMiddleware(new HandlersLocator([]))]);

        $provider = new UserProvider($this->createUserRepositoryStub(), $messageBus);
        $securityUser = $provider->loadUserByIdentifier('foobar@example.com');

        $provider->upgradePassword($securityUser, 'look-@-me.I am the new password now');
    }
}
