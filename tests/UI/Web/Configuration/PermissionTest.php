<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Configuration;

use ParkManager\Infrastructure\Security\Permission\IsFullOwner;
use ParkManager\UI\Web\Configuration\Permission;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PermissionTest extends TestCase
{
    /** @test */
    public function creates_permission_with_without_attributes(): void
    {
        $permission = new Permission(IsFullOwner::class);

        self::assertSame(
            'is_granted(permission("\\\\ParkManager\\\\Infrastructure\\\\Security\\\\Permission\\\\IsFullOwner"))',
            $permission->getExpression()
        );
        self::assertNull($permission->getStatusCode());
        self::assertSame('Access denied.', $permission->getMessage());
    }

    /** @test */
    public function creates_permission_with_with_attributes(): void
    {
        $permission = new Permission(IsFullOwner::class, ['space.owner']);

        self::assertSame(
            'is_granted(permission("\\\\ParkManager\\\\Infrastructure\\\\Security\\\\Permission\\\\IsFullOwner", space.owner))',
            $permission->getExpression()
        );
        self::assertNull($permission->getStatusCode());
        self::assertSame('Access denied.', $permission->getMessage());

        $permission = new Permission(IsFullOwner::class, ['space.owner', 'user']);

        self::assertSame(
            'is_granted(permission("\\\\ParkManager\\\\Infrastructure\\\\Security\\\\Permission\\\\IsFullOwner", space.owner, user))',
            $permission->getExpression()
        );
        self::assertNull($permission->getStatusCode());
        self::assertSame('Access denied.', $permission->getMessage());
    }

    /** @test */
    public function creates_permission_with_additional_arguments(): void
    {
        $permission = new Permission(IsFullOwner::class, statusCode: 505);

        self::assertSame(
            'is_granted(permission("\\\\ParkManager\\\\Infrastructure\\\\Security\\\\Permission\\\\IsFullOwner"))',
            $permission->getExpression()
        );
        self::assertSame(505, $permission->getStatusCode());
        self::assertSame('Access denied.', $permission->getMessage());

        $permission = new Permission(IsFullOwner::class, message: 'No access for you!');

        self::assertSame(
            'is_granted(permission("\\\\ParkManager\\\\Infrastructure\\\\Security\\\\Permission\\\\IsFullOwner"))',
            $permission->getExpression()
        );
        self::assertNull($permission->getStatusCode());
        self::assertSame('No access for you!', $permission->getMessage());
    }
}
