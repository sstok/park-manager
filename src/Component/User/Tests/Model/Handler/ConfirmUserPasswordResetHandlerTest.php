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

namespace ParkManager\Component\User\Tests\Model\Handler;

use ParkManager\Component\Security\Token\SplitToken;
use ParkManager\Component\User\Exception\PasswordResetConfirmationRejected;
use ParkManager\Component\User\Model\Command\ConfirmUserPasswordReset;
use ParkManager\Component\User\Model\Handler\ConfirmUserPasswordResetHandler;
use ParkManager\Component\User\Model\User;
use ParkManager\Component\User\Model\UserCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @internal
 */
final class ConfirmUserPasswordResetHandlerTest extends TestCase
{
    public const TOKEN_STRING = 'S1th74ywhDETYAaXWi-2Bee2_ltx-JPGKs9SVvbZCkMi8ZxiEVMBw68S';
    public const SELECTOR = 'S1th74ywhDETYAaXWi-2Bee2_ltx-JPG';

    /** @test */
    public function it_handles_password_reset_confirmation()
    {
        $token = SplitToken::fromString(self::TOKEN_STRING);
        $handler = new ConfirmUserPasswordResetHandler(
            $this->expectUserSaved(
                self::SELECTOR,
                $this->expectUserConfirmationTokenIsVerified($token, 'my-password')
            )
        );

        $command = new ConfirmUserPasswordReset($token, 'my-password');
        $handler($command);
    }

    /** @test */
    public function it_handles_password_reset_confirmation_with_failure()
    {
        $token = SplitToken::fromString(self::TOKEN_STRING);

        $handler = new ConfirmUserPasswordResetHandler(
            $this->expectUserSaved(
                self::SELECTOR,
                $this->expectUserConfirmationTokenIsVerified($token, 'my-password', false)
            )
        );

        $this->expectException(PasswordResetConfirmationRejected::class);
        $handler(new ConfirmUserPasswordReset($token, 'my-password'));
    }

    /** @test */
    public function it_handles_password_reset_confirmation_with_no_result()
    {
        $handler = new ConfirmUserPasswordResetHandler($this->expectUserNotSaved());

        $this->expectException(PasswordResetConfirmationRejected::class);
        $handler(new ConfirmUserPasswordReset(SplitToken::fromString(self::TOKEN_STRING), 'my-password-word'));
    }

    private function expectUserConfirmationTokenIsVerified(SplitToken $token, string $password, bool $result = true): User
    {
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->confirmPasswordReset($token, $password)->willReturn($result);

        return $userProphecy->reveal();
    }

    private function expectUserSaved(string $selector, User $user): UserCollection
    {
        $repositoryProphecy = $this->prophesize(UserCollection::class);
        $repositoryProphecy->findByPasswordResetToken($selector)->willReturn($user);
        $repositoryProphecy->save($user)->shouldBeCalledTimes(1);

        return $repositoryProphecy->reveal();
    }

    private function expectUserNotSaved(): UserCollection
    {
        $repositoryProphecy = $this->prophesize(UserCollection::class);
        $repositoryProphecy->findByPasswordResetToken(Argument::any())->willReturn(null);
        $repositoryProphecy->save(Argument::any())->shouldNotBeCalled();

        return $repositoryProphecy->reveal();
    }
}
