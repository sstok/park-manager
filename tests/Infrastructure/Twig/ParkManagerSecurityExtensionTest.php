<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Twig;

use ParkManager\Infrastructure\Twig\ParkManagerSecurityExtension;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal
 */
final class ParkManagerSecurityExtensionTest extends TestCase
{
    /** @test */
    public function it_throws_access_denied_when_not_authenticated(): void
    {
        $tokenStorage = new TokenStorage();
        $userRepository = new UserRepositoryMock();
        $extension = new ParkManagerSecurityExtension($tokenStorage, $userRepository);

        $this->expectException(AccessDeniedException::class);

        $extension->getCurrentUser();
    }

    /** @test */
    public function it_gets_currently_authenticated_user(): void
    {
        $userRepository = new UserRepositoryMock([$user = UserRepositoryMock::createUser()]);
        $securityUser = $user->toSecurityUser();

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken($securityUser, 'main', ['ROLE_USER']));
        $extension = new ParkManagerSecurityExtension($tokenStorage, $userRepository);

        self::assertSame($user, $extension->getCurrentUser());
    }
}
