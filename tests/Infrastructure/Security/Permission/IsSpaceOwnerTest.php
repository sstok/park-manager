<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Security\Permission;

use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Security\Permission\Webhosting\IsSpaceOwner;
use ParkManager\Infrastructure\Security\PermissionAccessManager;
use ParkManager\Infrastructure\Security\PermissionDecider;
use ParkManager\Infrastructure\Security\SecurityUser;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * @internal
 */
final class IsSpaceOwnerTest extends TestCase
{
    use ProphecyTrait;

    public const USER_ID = 'e29e2caf-5fc8-4314-9ecd-fd29708b412b';
    public const USER_ID2 = '8e37a3b4-c61e-42c2-a184-c3696b1e21fa';

    /** @test */
    public function it_decides_deny_if_space_is_private_owned_and_user_is_not_admin(): void
    {
        $securityUser = new SecurityUser(self::USER_ID, 'Nope', true, ['ROLE_USER']);
        $token = $this->createAuthenticationToken($securityUser);

        $permission = new IsSpaceOwner(Space::registerWithCustomConstraints(SpaceId::create(), null, new Constraints()));
        self::assertEquals(PermissionDecider::DECIDE_DENY, $permission($token, $securityUser, $this->createMock(PermissionAccessManager::class)));
    }

    private function createAuthenticationToken(SecurityUser $securityUser): AbstractToken
    {
        $token = $this->getMockForAbstractClass(AbstractToken::class, [$securityUser->getRoles()]);
        $token->setUser($securityUser);

        return $token;
    }

    /** @test */
    public function it_decides_allow_if_space_is_private_owned_and_user_is_admin(): void
    {
        $securityUser = new SecurityUser(self::USER_ID, 'Nope', true, ['ROLE_USER', 'ROLE_ADMIN']);
        $token = $this->createAuthenticationToken($securityUser);

        $permission = new IsSpaceOwner(Space::registerWithCustomConstraints(SpaceId::create(), null, new Constraints()));
        self::assertEquals(PermissionDecider::DECIDE_ALLOW, $permission($token, $securityUser, $this->createMock(PermissionAccessManager::class)));
    }

    /** @test */
    public function it_decides_allow_if_space_owner_equals_user_id(): void
    {
        $securityUser = new SecurityUser(self::USER_ID, 'Nope', true, ['ROLE_USER']);
        $token = $this->createAuthenticationToken($securityUser);

        $permission = new IsSpaceOwner(Space::registerWithCustomConstraints(SpaceId::create(), $this->getUser(), new Constraints()));
        self::assertEquals(PermissionDecider::DECIDE_ALLOW, $permission($token, $securityUser, $this->createMock(PermissionAccessManager::class)));
    }

    /** @test */
    public function it_decides_abstain_if_space_owner_does_not_equal_user_id(): void
    {
        $securityUser = new SecurityUser(self::USER_ID2, 'Nope', true, ['ROLE_USER']);
        $token = $this->createAuthenticationToken($securityUser);

        $permission = new IsSpaceOwner(Space::registerWithCustomConstraints(SpaceId::create(), $this->getUser(), new Constraints()));
        self::assertEquals(PermissionDecider::DECIDE_ABSTAIN, $permission($token, $securityUser, $this->createMock(PermissionAccessManager::class)));
    }

    private function getUser(): User
    {
        $user = $this->prophesize(User::class);
        $user->id = UserId::fromString(self::USER_ID);

        return $user->reveal();
    }
}
