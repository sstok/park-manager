<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Security\EventListener;

use AssertionError;
use ParkManager\Application\Event\UserPasswordWasChanged;
use ParkManager\Infrastructure\Security\EventListener\AuthenticationTokenPasswordChangedListener;
use ParkManager\Infrastructure\Security\SecurityUser;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @internal
 */
final class AuthenticationTokenPasswordChangedListenerTest extends TestCase
{
    use ProphecyTrait;

    private const ID1 = '930c3fd0-3bd1-11e7-bb9b-acdc32b58315';
    private const ID2 = '930c3fd0-3bd1-11e7-bb9b-acdc32b58318';

    /** @test */
    public function it_ignores_when_no_token_was_set(): void
    {
        $userProvider = $this->createUserProviderWithRefresh();
        $tokenStorage = $this->createProvidingOnlyTokenStorage(null);

        $listener = new AuthenticationTokenPasswordChangedListener($userProvider, $tokenStorage);
        $listener->onUserPasswordWasChanged(new UserPasswordWasChanged(self::ID1, 'passwd'));
    }

    private function createUserProviderWithRefresh(): UserProviderInterface
    {
        $userProviderProphecy = $this->prophesize(UserProviderInterface::class);
        $userProviderProphecy->refreshUser(Argument::any())->shouldNotBeCalled();

        return $userProviderProphecy->reveal();
    }

    private function createProvidingOnlyTokenStorage(?TokenInterface $token): TokenStorageInterface
    {
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorageProphecy->setToken(Argument::any())->shouldNotBeCalled();

        return $tokenStorageProphecy->reveal();
    }

    /** @test */
    public function it_ignores_when_token_is_not_authenticated(): void
    {
        $userProvider = $this->createUserProviderWithRefresh();
        $token = new NullToken();

        $tokenStorage = $this->createProvidingOnlyTokenStorage($token);

        $listener = new AuthenticationTokenPasswordChangedListener($userProvider, $tokenStorage);
        $listener->onUserPasswordWasChanged(new UserPasswordWasChanged(self::ID1, 'passwd'));

        self::assertNull($token->getUser());
    }

    /** @test */
    public function it_ignores_when_token_is_switch_user(): void
    {
        $userProvider = $this->createUserProviderWithRefresh();
        $origToken = new UsernamePasswordToken($this->createUser1(), 'main', ['ROLE_USER']);
        $token = new SwitchUserToken($user2 = new SecurityUser(self::ID2, 'w-px', true, ['ROLE_USER']), 'main', ['ROLE_USER'], $origToken);

        $tokenStorage = $this->createProvidingOnlyTokenStorage($token);

        $listener = new AuthenticationTokenPasswordChangedListener($userProvider, $tokenStorage);
        $listener->onUserPasswordWasChanged(new UserPasswordWasChanged(self::ID2, 'passwd'));

        self::assertSame($user2, $token->getUser());
    }

    private function createUser1(string $password = 'pass-north'): SecurityUser
    {
        return new SecurityUser(self::ID1, $password, true, ['ROLE_USER']);
    }

    /** @test */
    public function it_ignores_when_user_is_not_a_security_user(): void
    {
        $user = $this->createMock(NewUser::class);
        $user->method('getUserIdentifier')->willReturn(self::ID1);
        $userProvider = $this->createUserProviderWithRefresh();
        $tokenStorage = $this->createProvidingOnlyTokenStorage($token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']));

        $listener = new AuthenticationTokenPasswordChangedListener($userProvider, $tokenStorage);
        $listener->onUserPasswordWasChanged(new UserPasswordWasChanged(self::ID1, 'passwd'));

        self::assertSame($user, $token->getUser());
    }

    /** @test */
    public function it_ignores_when_refreshed_user_is_not_enabled(): void
    {
        $currentUser = $this->createUser1();
        $userProvider = $this->createUserProviderExpectsCurrentUser($currentUser, $this->createUser1NonActive());
        $tokenStorage = $this->createProvidingOnlyTokenStorage($token = new UsernamePasswordToken($currentUser, 'main', ['ROLE_USER']));

        $listener = new AuthenticationTokenPasswordChangedListener($userProvider, $tokenStorage);
        $listener->onUserPasswordWasChanged(new UserPasswordWasChanged(self::ID1, 'passwd'));

        self::assertSame($currentUser, $token->getUser());
    }

    private function createUserProviderExpectsCurrentUser(UserInterface $currentUser, UserInterface $user): UserProviderInterface
    {
        $userProviderProphecy = $this->prophesize(UserProviderInterface::class);
        $userProviderProphecy->refreshUser($currentUser)->willReturn($user)->shouldBeCalled();

        return $userProviderProphecy->reveal();
    }

    private function createUser1NonActive(): SecurityUser
    {
        return new SecurityUser(self::ID1, 'pass-north', false, ['ROLE_USER']);
    }

    /** @test */
    public function it_only_updates_token_when_current_user(): void
    {
        $userProvider = $this->createUserProviderWithRefresh();
        $currentUser = new SecurityUser(self::ID2, 'pass-north', true, ['ROLE_USER']);
        $tokenStorage = $this->createProvidingOnlyTokenStorage($token = new UsernamePasswordToken($currentUser, 'main', ['ROLE_USER']));

        $listener = new AuthenticationTokenPasswordChangedListener($userProvider, $tokenStorage);
        $listener->onUserPasswordWasChanged(new UserPasswordWasChanged(self::ID1, 'passwd'));

        self::assertSame($currentUser, $token->getUser());
    }

    /** @test */
    public function it_checks_the_correct_user_provider_was_used(): void
    {
        $currentUser = $this->createUser1();
        $userProvider = $this->createUserProviderExpectsCurrentUser($currentUser, $this->createMock(NewUser::class));
        $tokenStorage = $this->createProvidingOnlyTokenStorage(new UsernamePasswordToken($currentUser, 'main', ['ROLE_USER']));

        $listener = new AuthenticationTokenPasswordChangedListener($userProvider, $tokenStorage);

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('assert($user instanceof SecurityUser)');

        $listener->onUserPasswordWasChanged(new UserPasswordWasChanged(self::ID1, 'passwd'));
    }

    /** @test */
    public function it_marks_token_as_authenticated_and_sets_on_storage(): void
    {
        $token = new UsernamePasswordToken($currentUser = $this->createUser1(), 'main', ['ROLE_USER']);
        $userProvider = $this->createUserProviderExpectsCurrentUser($currentUser, $newUser = $this->createUser1('pass-north2'));
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $listener = new AuthenticationTokenPasswordChangedListener($userProvider, $tokenStorage);
        $listener->onUserPasswordWasChanged(new UserPasswordWasChanged(self::ID1, 'passwd'));

        $newToken = $tokenStorage->getToken();

        self::assertSame($newUser, $newToken->getUser());
    }
}

/**
 * @internal
 */
interface NewUser extends UserInterface
{
    public function getUserIdentifier(): string;
}
