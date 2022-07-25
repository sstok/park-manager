<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Security;

use ParkManager\Infrastructure\Security\AliasedPermission;
use ParkManager\Infrastructure\Security\Permission;
use ParkManager\Infrastructure\Security\PermissionAccessManager;
use ParkManager\Infrastructure\Security\PermissionDecider;
use ParkManager\Infrastructure\Security\PermissionExpression;
use ParkManager\Infrastructure\Security\SecurityUser;
use ParkManager\Infrastructure\Security\SelfDecidingPermission;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
final class PermissionAccessManagerTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_decides_deny_if_no_token_is_set(): void
    {
        $tokenStorage = $this->createTokenStorage();
        $manager = new PermissionAccessManager($tokenStorage, new Container(), []);

        self::assertSame(PermissionDecider::DECIDE_DENY, $manager->decide($this->createMock(Permission::class), null));
    }

    private function createTokenStorage(?TokenInterface $token = null): TokenStorageInterface
    {
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);

        return $tokenStorageProphecy->reveal();
    }

    /** @test */
    public function it_decides_deny_if_provided_token_is_not_authenticated(): void
    {
        $token = $this->createToken();
        $tokenStorage = $this->createTokenStorage();
        $manager = new PermissionAccessManager($tokenStorage, new Container(), []);

        self::assertSame(PermissionDecider::DECIDE_DENY, $manager->decide($this->createMock(Permission::class), $token));
    }

    private function createToken(?UserInterface $user = null): TokenInterface
    {
        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getUser()->willReturn($user);

        return $tokenProphecy->reveal();
    }

    /** @test */
    public function it_decides_deny_if_stored_token_is_not_authenticated(): void
    {
        $tokenStorage = $this->createTokenStorage($this->createToken());
        $manager = new PermissionAccessManager($tokenStorage, new Container(), []);

        self::assertSame(PermissionDecider::DECIDE_DENY, $manager->decide($this->createMock(Permission::class)));
    }

    /** @test */
    public function it_decides_deny_if_user_is_not_a_security_user(): void
    {
        $tokenStorage = $this->createTokenStorage($this->createToken($this->createMock(UserInterface::class)));
        $manager = new PermissionAccessManager($tokenStorage, new Container(), []);

        self::assertSame(PermissionDecider::DECIDE_DENY, $manager->decide($this->createMock(Permission::class)));
    }

    private function getSecurityUser(): SecurityUser
    {
        return new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_USER']);
    }

    /** @test */
    public function it_executes_decider(): void
    {
        $tokenStorage = $this->createTokenStorage($this->createToken($this->getSecurityUser()));
        $deciders = new Container();
        $deciders->set(MockPermission::class, new class() implements PermissionDecider {
            /**
             * @param MockPermission&Permission $permission
             */
            public function decide(Permission $permission, TokenInterface $token, SecurityUser $user, PermissionAccessManager $permissionAccess): int
            {
                return $permission->permission;
            }
        });
        $manager = new PermissionAccessManager($tokenStorage, $deciders, []);

        self::assertSame(PermissionDecider::DECIDE_ALLOW, $manager->decide(new MockPermission(PermissionDecider::DECIDE_ALLOW), null));
        self::assertSame(PermissionDecider::DECIDE_DENY, $manager->decide(new MockPermission(PermissionDecider::DECIDE_DENY), null));
        self::assertSame(PermissionDecider::DECIDE_DENY, $manager->decide(new MockPermission(PermissionDecider::DECIDE_DENY), null));
    }

    /** @test */
    public function it_executes_self_deciding_permission(): void
    {
        $tokenStorage = $this->createTokenStorage($this->createToken($this->getSecurityUser()));
        $manager = new PermissionAccessManager($tokenStorage, new Container(), []);

        self::assertSame(PermissionDecider::DECIDE_ALLOW, $manager->decide(new MockSelfPermission(PermissionDecider::DECIDE_ALLOW), null));
        self::assertSame(PermissionDecider::DECIDE_DENY, $manager->decide(new MockSelfPermission(PermissionDecider::DECIDE_DENY), null));
    }

    /** @test */
    public function it_resolves_aliased_permission(): void
    {
        $tokenStorage = $this->createTokenStorage($this->createToken($this->getSecurityUser()));
        $deciders = new Container();
        $deciders->set(MockPermission::class, new class() implements PermissionDecider {
            /**
             * @param MockPermission&Permission $permission
             */
            public function decide(Permission $permission, TokenInterface $token, SecurityUser $user, PermissionAccessManager $permissionAccess): int
            {
                return $permission->permission;
            }
        });
        $manager = new PermissionAccessManager($tokenStorage, $deciders, []);

        self::assertSame(PermissionDecider::DECIDE_ALLOW, $manager->decide(new MockAliasedPermission(PermissionDecider::DECIDE_ALLOW), null));
        self::assertSame(PermissionDecider::DECIDE_DENY, $manager->decide(new MockAliasedPermission(PermissionDecider::DECIDE_DENY), null));
    }

    /** @test */
    public function it_resolves_short_aliased_permission(): void
    {
        $tokenStorage = $this->createTokenStorage($this->createToken($this->getSecurityUser()));
        $deciders = new Container();
        $deciders->set(MockPermission::class, new class() implements PermissionDecider {
            /**
             * @param MockPermission&Permission $permission
             */
            public function decide(Permission $permission, TokenInterface $token, SecurityUser $user, PermissionAccessManager $permissionAccess): int
            {
                return $permission->permission;
            }
        });
        $manager = new PermissionAccessManager($tokenStorage, $deciders, ['is_owner' => MockAliasedPermission::class]);

        self::assertSame(PermissionDecider::DECIDE_ALLOW, $manager->decide(new PermissionExpression('is_owner', PermissionDecider::DECIDE_ALLOW), null));
        self::assertSame(PermissionDecider::DECIDE_ALLOW, $manager->decide(new PermissionExpression(MockPermission::class, PermissionDecider::DECIDE_ALLOW), null));
    }

    /** @test */
    public function it_gives_suggestions_for_unresolvable_short_alias(): void
    {
        $tokenStorage = $this->createTokenStorage($this->createToken($this->getSecurityUser()));
        $manager = new PermissionAccessManager($tokenStorage, new Container(), ['is_owner' => MockAliasedPermission::class, 'is_space_owner' => MockAliasedPermission::class]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("No Permission can be found for short-name \"is_owmer\".\nDid you e.g. mean \"is_owner\"");

        $manager->decide(new PermissionExpression('is_owmer', PermissionDecider::DECIDE_DENY));
    }

    /** @test */
    public function it_gives_provides_names_for_unresolvable_short_alias_with_no_match(): void
    {
        $tokenStorage = $this->createTokenStorage($this->createToken($this->getSecurityUser()));
        $manager = new PermissionAccessManager($tokenStorage, new Container(), ['is_owner' => MockAliasedPermission::class, 'is_space_owner' => MockAliasedPermission::class]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("No Permission can be found for short-name \"is_homer\".\nSupported \"is_owner\", \"is_space_owner\"");

        $manager->decide(new PermissionExpression('is_homer', PermissionDecider::DECIDE_DENY));
    }

    /** @test */
    public function it_checks_a_decider_registered_for_permission(): void
    {
        $tokenStorage = $this->createTokenStorage($this->createToken($this->getSecurityUser()));
        $manager = new PermissionAccessManager($tokenStorage, new Container(), []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('No Decider is registered for Permission "%s".', MockPermission::class));

        $manager->decide(new PermissionExpression(MockPermission::class, PermissionDecider::DECIDE_ALLOW));
    }
}

class MockPermission implements Permission
{
    public int $permission;

    public function __construct(int $permission)
    {
        $this->permission = $permission;
    }
}

class MockSelfPermission implements SelfDecidingPermission
{
    public int $permission;

    public function __construct(int $permission)
    {
        $this->permission = $permission;
    }

    public function __invoke(TokenInterface $token, SecurityUser $user, PermissionAccessManager $permissionAccess): int
    {
        return $this->permission;
    }
}

class MockAliasedPermission extends MockPermission implements AliasedPermission
{
    public function getAlias(): string
    {
        return '\\' . MockPermission::class;
    }
}
