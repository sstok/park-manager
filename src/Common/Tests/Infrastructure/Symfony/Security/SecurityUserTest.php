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

namespace ParkManager\Common\Tests\Infrastructure\Symfony\Security;

use ParkManager\Common\Infrastructure\Symfony\Security\SecurityUser;
use ParkManager\Common\Model\EmailAddress;
use ParkManager\Common\Model\HoldsIdentity;
use ParkManager\Common\Model\Security\AuthenticationInfo;
use ParkManager\Common\Model\Security\EmailAddressAndPasswordAuthentication;
use ParkManager\Common\Projection\UserReadModel;
use ParkManager\Common\Test\Model\Security\MockAuthentication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUserTest extends TestCase
{
    private const ID1 = '930c3fd0-3bd1-11e7-bb9b-acbc32b58315';

    /** @test */
    public function it_implements_UserInterface()
    {
        $securityUser = new SecurityUser($this->createUser());

        self::assertInstanceOf(UserInterface::class, $securityUser);
    }

    /** @test */
    public function its_username_equals_id()
    {
        $securityUser = new SecurityUser($this->createUser());

        self::assertSame(self::ID1, $securityUser->getUsername());
    }

    /** @test */
    public function its_password_is_empty_when_not_provided()
    {
        $securityUser = new SecurityUser($this->createUser());

        self::assertSame('', $securityUser->getPassword());
    }

    /** @test */
    public function its_password_is_provided_when_authInfo_is()
    {
        $securityUser = new SecurityUser(
            $this->createUser(new EmailAddressAndPasswordAuthentication(new EmailAddress('admin@example.com'), 'my-password'))
        );

        self::assertSame('my-password', $securityUser->getPassword());
    }

    /** @test */
    public function it_has_ROLE_USER()
    {
        $securityUser = new SecurityUser($this->createUser());

        self::assertContains('ROLE_USER', $securityUser->getRoles());
    }

    /** @test */
    public function its_serializable()
    {
        $authentication = new MockAuthentication(['user-3']);
        $securityUser = new SecurityUser($this->createUser($authentication));
        $restoredSecurityUser = unserialize(serialize($securityUser), []);

        self::assertEquals($restoredSecurityUser, $securityUser);

        // Test for access-disabled.
        $securityUser = new SecurityUser($this->createUser(null, false));
        $restoredSecurityUser = unserialize(serialize($securityUser), []);

        self::assertEquals($restoredSecurityUser, $securityUser);
    }

    /** @test */
    public function its_equatable()
    {
        $authentication = new MockAuthentication(['user-3']);

        $securityUser = new SecurityUser($this->createUser($authentication, false));
        $securityUser2 = new SecurityUser($this->createUser($authentication, false));
        $securityUser3 = new SecurityUser($this->createUser());

        self::assertTrue($securityUser->isEqualTo($securityUser));
        self::assertTrue($securityUser->isEqualTo($securityUser2));

        self::assertFalse($securityUser->isEqualTo($securityUser3));
        self::assertFalse($securityUser->isEqualTo($this->createMock(SecurityUser::class)));
        self::assertFalse($securityUser->isEqualTo($this->createMock(UserInterface::class)));
    }

    /** @test */
    public function its_equatable_with_disabled()
    {
        $securityUser = new SecurityUser($this->createUser());

        $disabledSecurityUser = new SecurityUser($this->createUser(null, false));

        self::assertTrue($disabledSecurityUser->isEqualTo($disabledSecurityUser));
        self::assertFalse($securityUser->isEqualTo($disabledSecurityUser));
    }

    /** @test */
    public function its_equatable_with_diff_auth()
    {
        $authentication = new MockAuthentication(['user-1']);
        $securityUser = new SecurityUser($this->createUser($authentication));

        $authentication = new MockAuthentication(['user-2']);
        $securityUserWithDifferentAuth = new SecurityUser($this->createUser($authentication));

        self::assertTrue($securityUserWithDifferentAuth->isEqualTo($securityUserWithDifferentAuth));
        self::assertFalse($securityUser->isEqualTo($securityUserWithDifferentAuth));
    }

    private function createUser(AuthenticationInfo $authentication = null, bool $enabled = true): UserReadModel
    {
        if (!isset($authentication)) {
            $authentication = new MockAuthentication(['user-1']);
        }

        $id = $this->getObjectForTrait(HoldsIdentity::class)::fromString(self::ID1);

        $user = $this->prophesize(UserReadModel::class);
        $user->id()->willReturn($id);
        $user->isAccessEnabled()->willReturn($enabled);
        $user->authenticationInfo()->willReturn($authentication);

        return $user->reveal();
    }
}
