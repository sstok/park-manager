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

use ParkManager\Bundle\ServiceBusBundle\Guard\CliGuard;
use ParkManager\Bundle\ServiceBusBundle\Guard\SymfonyGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @internal
 *
 * Note. PHPUnit is already run using PHP-cli, so these tests are limited.
 */
final class CliGuardTest extends TestCase
{
    /** @test */
    public function it_decides_allow_if_sapi_cli_is_cli_and_there_is_no_token()
    {
        $guard = new CliGuard($this->createTokenStorage(null));

        self::assertEquals(SymfonyGuard::PERMISSION_ALLOW, $guard->decide(new \stdClass()));
    }

    /** @test */
    public function it_decides_abstain_if_sapi_is_cli_there_is_a_token()
    {
        $guard = new CliGuard($this->createTokenStorage($this->createMock(TokenInterface::class)));

        self::assertEquals(SymfonyGuard::PERMISSION_ABSTAIN, $guard->decide(new \stdClass()));
    }

    private function createTokenStorage(?TokenInterface $token): TokenStorageInterface
    {
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);

        return $tokenStorageProphecy->reveal();
    }
}
