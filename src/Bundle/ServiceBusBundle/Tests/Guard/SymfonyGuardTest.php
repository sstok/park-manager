<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\ServiceBusBundle\Tests\Guard;

use ParkManager\Bundle\ServiceBusBundle\Guard\SymfonyGuard;
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
        $guard   = new SymfonyGuard($this->createAuthorizationChecker($message, true));

        self::assertEquals(SymfonyGuard::PERMISSION_ALLOW, $guard->decide($message));
    }

    /** @test */
    public function it_decides_abstain_if_access_denied_by_symfony()
    {
        $message = new \stdClass();
        $guard   = new SymfonyGuard($this->createAuthorizationChecker($message, false));

        self::assertEquals(SymfonyGuard::PERMISSION_ABSTAIN, $guard->decide($message));
    }

    private function createAuthorizationChecker(object $message, bool $granted): AuthorizationCheckerInterface
    {
        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted([], $message)->willReturn($granted);

        return $authorizationCheckerProphecy->reveal();
    }
}
