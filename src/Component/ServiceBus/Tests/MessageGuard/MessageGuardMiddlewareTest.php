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

namespace ParkManager\Component\ServiceBus\Tests\MessageGuard;

use ParkManager\Component\ServiceBus\MessageGuard\MessageGuardMiddleware;
use ParkManager\Component\ServiceBus\MessageGuard\PermissionGuard;
use ParkManager\Component\ServiceBus\MessageGuard\UnauthorizedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Debug\BufferingLogger;

/**
 * @internal
 */
final class MessageGuardMiddlewareTest extends TestCase
{
    /** @test */
    public function it_denies_access_no_guards_are_registered()
    {
        $messageGuard = new MessageGuardMiddleware([]);

        $this->expectException(UnauthorizedException::class);

        $messageGuard->execute(new \stdClass(), function () {});
    }

    /** @test */
    public function it_stops_when_a_guard_decides_to_deny()
    {
        $message = new \stdClass();

        $messageGuard = new MessageGuardMiddleware([
            $this->createPermissionGuard($message, PermissionGuard::PERMISSION_ABSTAIN),
            $this->createPermissionGuard($message, PermissionGuard::PERMISSION_DENY),
            $this->createNotExecutedPermissionGuard(),
        ]);

        $this->expectException(UnauthorizedException::class);

        $messageGuard->execute($message, function () {});
    }

    /** @test */
    public function it_stops_when_a_guard_decides_to_allow()
    {
        $message = new \stdClass();

        $messageGuard = new MessageGuardMiddleware([
            $guard1 = $this->createPermissionGuard($message, PermissionGuard::PERMISSION_ABSTAIN),
            $guard2 = $this->createPermissionGuard($message, PermissionGuard::PERMISSION_ALLOW),
            $this->createNotExecutedPermissionGuard(),
        ], $logger = new BufferingLogger());

        $returnValue = $messageGuard->execute($message, function ($command) {
            return $command;
        });

        self::assertSame($message, $returnValue);
        self::assertEquals([
            ['info', 'PermissionGuard "'.get_class($guard1).'" decides: ABSTAIN', []],
            ['info', 'PermissionGuard "'.get_class($guard2).'" decides: ALLOW', []],
        ], $logger->cleanLogs());
    }

    /** @test */
    public function it_denies_when_all_guards_decides_to_abstain()
    {
        $message = new \stdClass();

        $messageGuard = new MessageGuardMiddleware([
            $this->createPermissionGuard($message, PermissionGuard::PERMISSION_ABSTAIN),
            $this->createPermissionGuard($message, PermissionGuard::PERMISSION_ABSTAIN),
        ]);

        $this->expectException(UnauthorizedException::class);

        $messageGuard->execute($message, function () {});
    }

    /** @test */
    public function it_validates_guard_decision_is_valid()
    {
        $message = new \stdClass();

        $messageGuard = new MessageGuardMiddleware([$guard = $this->createPermissionGuard($message, 3)]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('PermissionGuard "%s" returned unsupported decision %d', get_class($guard), 3)
        );

        $messageGuard->execute($message, function () {});
    }

    private function createPermissionGuard(object $message, int $permission): PermissionGuard
    {
        $guard = $this->createMock(PermissionGuard::class);
        $guard
            ->expects(self::once())
            ->method('decide')
            ->with($message)
            ->willReturn($permission);

        return $guard;
    }

    private function createNotExecutedPermissionGuard(): PermissionGuard
    {
        $guard = $this->createMock(PermissionGuard::class);
        $guard
            ->expects(self::never())
            ->method('decide');

        return $guard;
    }
}
