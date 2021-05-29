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

        self::assertSame(self::ID1, $securityUser->getUsername());
        self::assertSame(self::ID2, $securityUser2->getUsername());
        self::assertSame(self::ID2, $securityUser2->getUserIdentifier());
        self::assertSame(self::ID1, $securityUser->getId());
        self::assertSame(self::ID2, $securityUser2->getId());
    }

    /** @test */
    public function its_password_is_equals_when_provided(): void
    {
        self::assertSame(self::PASSWORD, $this->createSecurityUser()->getPassword());
    }

    /** @test */
    public function it_has_roles(): void
    {
        $securityUser = $this->createSecurityUser();
        self::assertSame(['ROLE_USER'], $securityUser->getRoles());
        self::assertFalse($securityUser->isAdmin());
        self::assertFalse($securityUser->isSuperAdmin());

        $adminUser = $this->createSecurityAdminUser();
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $adminUser->getRoles());
        self::assertTrue($adminUser->isAdmin());
        self::assertFalse($adminUser->isSuperAdmin());

        $superAdminUser = $this->createSecuritySuperAdminUser();
        self::assertTrue($superAdminUser->isSuperAdmin());
        self::assertTrue($superAdminUser->isSuperAdmin());
    }

    /** @test */
    public function it_equals_other_instance_with_same_information(): void
    {
        $securityUser1 = $this->createSecurityUser();
        $securityUser2 = $this->createSecurityUser();

        self::assertTrue($securityUser1->isEqualTo($securityUser2));
    }

    /** @test */
    public function it_does_not_equal_other_instance_with_different_information(): void
    {
        $securityUser1 = $this->createSecurityUser();
        $securityUser2 = $this->createSecurityUser(self::ID2); // id
        $securityUser3 = $this->createSecurityUser(self::ID1, 'ding-ding'); // password
        $securityUser4 = $this->createSecurityUserSecond(self::ID1); // Different class
        $securityUser5 = $this->createSecurityAdminUser();
        $securityUser6 = new SecurityUser(self::ID1, self::PASSWORD, false, ['ROLE_USER']); // Status
        $securityUser7 = $this->createSecurityAdminUser();

        self::assertFalse($securityUser1->isEqualTo($securityUser2), 'ID should mismatch');
        self::assertFalse($securityUser1->isEqualTo($securityUser3), 'Password should mismatch');
        self::assertFalse($securityUser1->isEqualTo($securityUser4), 'Class should be of same instance');
        self::assertFalse($securityUser1->isEqualTo($securityUser6), 'Enabled status should mismatch');
        self::assertFalse($securityUser1->isEqualTo($securityUser5), 'Roles should mismatch');
        self::assertTrue($securityUser5->isEqualTo($securityUser7), 'Roles order should not mismatch');
    }

    /** @test */
    public function its_serializable(): void
    {
        $securityUser = new SecurityUser(self::ID1, self::PASSWORD, false, ['ROLE_USER', 'ROLE_OPERATOR']);
        $unserialized = unserialize(serialize($securityUser), []);

        self::assertTrue($securityUser->isEqualTo($unserialized));
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

    private function createSecurityUserSecond(string $username): UserInterface
    {
        return new class($username, self::PASSWORD, ['ROLE_USER']) implements UserInterface {
            /**
             * @param array<int, string> $roles
             */
            public function __construct(private string $identifier, private string $password, private array $roles)
            {
            }

            /**
             * @return array<int, string>
             */
            public function getRoles(): array
            {
                return $this->roles;
            }

            public function getPassword(): ?string
            {
                return $this->password;
            }

            public function getSalt(): ?string
            {
                return null;
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return $this->identifier;
            }
        };
    }
}
