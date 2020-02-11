<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Security;

use ParkManager\Infrastructure\Security\SecurityUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
final class SecurityUserTest extends TestCase
{
    private const ID1 = '930c3fd0-3bd1-11e7-bb9b-acdc32b58315';
    private const ID2 = 'c831846c-53f6-11e7-aceb-acbc32b58315';
    private const PASSWORD = 'my-password-is-better-than-your-password';

    /** @test */
    public function its_username_equals_id(): void
    {
        $securityUser = $this->createSecurityUser();
        $securityUser2 = $this->createSecurityUser(self::ID2);

        static::assertSame(self::ID1, $securityUser->getUsername());
        static::assertSame(self::ID2, $securityUser2->getUsername());
        static::assertSame(self::ID1, $securityUser->getId());
        static::assertSame(self::ID2, $securityUser2->getId());
    }

    /** @test */
    public function its_password_is_equals_when_provided(): void
    {
        static::assertSame(self::PASSWORD, $this->createSecurityUser()->getPassword());
    }

    /** @test */
    public function it_has_roles(): void
    {
        $securityUser = $this->createSecurityUser();
        static::assertSame(['ROLE_USER'], $securityUser->getRoles());
        static::assertFalse($securityUser->isAdmin());
        static::assertFalse($securityUser->isSuperAdmin());

        $adminUser = $this->createSecurityAdminUser();
        static::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $adminUser->getRoles());
        static::assertTrue($adminUser->isAdmin());
        static::assertFalse($adminUser->isSuperAdmin());

        $superAdminUser = $this->createSecuritySuperAdminUser();
        static::assertTrue($superAdminUser->isSuperAdmin());
        static::assertTrue($superAdminUser->isSuperAdmin());
    }

    /** @test */
    public function it_equals_other_instance_with_same_information(): void
    {
        $securityUser1 = $this->createSecurityUser();
        $securityUser2 = $this->createSecurityUser();

        static::assertTrue($securityUser1->isEqualTo($securityUser2));
    }

    /** @test */
    public function it_does_not_equal_other_instance_with_different_information(): void
    {
        $securityUser1 = $this->createSecurityUser();
        $securityUser2 = $this->createSecurityUser(self::ID2); // id
        $securityUser3 = $this->createSecurityUser(self::ID1, 'ding-ding'); // password
        $securityUser4 = $this->createSecurityUserSecond(); // Different class
        $securityUser5 = $this->createSecurityAdminUser();
        $securityUser6 = new SecurityUser(self::ID1, self::PASSWORD, false, ['ROLE_USER']); // Status
        $securityUser7 = $this->createSecurityAdminUser();

        static::assertFalse($securityUser1->isEqualTo($securityUser2), 'ID should mismatch');
        static::assertFalse($securityUser1->isEqualTo($securityUser3), 'Password should mismatch');
        static::assertFalse($securityUser1->isEqualTo($securityUser4), 'Class should be of same instance');
        static::assertFalse($securityUser1->isEqualTo($securityUser6), 'Enabled status should mismatch');
        static::assertFalse($securityUser1->isEqualTo($securityUser5), 'Roles should mismatch');
        static::assertTrue($securityUser5->isEqualTo($securityUser7), 'Roles order should not mismatch');
    }

    /** @test */
    public function its_serializable(): void
    {
        $securityUser = new SecurityUser(self::ID1, self::PASSWORD, false, ['ROLE_USER', 'ROLE_OPERATOR']);
        $unserialized = \unserialize(\serialize($securityUser), []);

        static::assertTrue($securityUser->isEqualTo($unserialized));
    }

    private function createSecurityUser(?string $id = self::ID1, string $password = self::PASSWORD): SecurityUser
    {
        return new SecurityUser($id ?? self::ID1, $password, true, ['ROLE_USER']);
    }

    private function createSecurityAdminUser(?string $id = self::ID1, string $password = self::PASSWORD): SecurityUser
    {
        return new SecurityUser($id ?? self::ID1, $password, true, ['ROLE_USER', 'ROLE_ADMIN']);
    }

    private function createSecuritySuperAdminUser(?string $id = self::ID1, string $password = self::PASSWORD): SecurityUser
    {
        return new SecurityUser($id ?? self::ID1, $password, true, ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
    }

    private function createSecurityUserSecond(): UserInterface
    {
        return $this->createMock(UserInterface::class);
    }
}
