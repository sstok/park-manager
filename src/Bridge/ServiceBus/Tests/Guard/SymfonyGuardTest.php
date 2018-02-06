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

namespace ParkManager\Bridge\ServiceBus\Tests\Guard;

use ParkManager\Bridge\ServiceBus\Guard\SymfonyGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 */
final class SymfonyGuardTest extends TestCase
{
    /** @test */
    public function it_decides_allow_if_access_granted_by_symfony()
    {
        $message = new \stdClass();
        $guard = new SymfonyGuard($this->createAuthorizationChecker($message, true));

        self::assertEquals(SymfonyGuard::PERMISSION_ALLOW, $guard->decide($message));
    }

    /** @test */
    public function it_decides_abstain_if_access_denied_by_symfony()
    {
        $message = new \stdClass();
        $guard = new SymfonyGuard($this->createAuthorizationChecker($message, false));

        self::assertEquals(SymfonyGuard::PERMISSION_ABSTAIN, $guard->decide($message));
    }

    private function createAuthorizationChecker(object $message, bool $granted): AuthorizationCheckerInterface
    {
        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted([], $message)->willReturn($granted);

        return $authorizationCheckerProphecy->reveal();
    }
}
