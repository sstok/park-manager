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
use ParkManager\Component\User\Exception\EmailChangeConfirmationRejected;
use ParkManager\Component\User\Model\Command\ConfirmEmailAddressChange;
use ParkManager\Component\User\Model\Handler\ConfirmEmailAddressChangeHandler;
use ParkManager\Component\User\Model\User;
use ParkManager\Component\User\Model\UserCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @internal
 */
final class ConfirmEmailAddressChangeHandlerTest extends TestCase
{
    public const TOKEN_STRING = 'S1th74ywhDETYAaXWi-2Bee2_ltx-JPGKs9SVvbZCkMi8ZxiEVMBw68S';
    public const SELECTOR = 'S1th74ywhDETYAaXWi-2Bee2_ltx-JPG';

    /** @test */
    public function it_handles_emailAddress_change_confirmation()
    {
        $handler = new ConfirmEmailAddressChangeHandler(
            $this->expectUserSaved(
                self::SELECTOR,
                $this->expectUserConfirmationTokenIsVerified(SplitToken::fromString(self::TOKEN_STRING))
            )
        );

        $command = new ConfirmEmailAddressChange(self::TOKEN_STRING);
        $handler($command);
    }

    /** @test */
    public function it_handles_emailAddress_change_confirmation_with_failure()
    {
        $handler = new ConfirmEmailAddressChangeHandler(
            $this->expectUserSaved(
                self::SELECTOR,
                $this->expectUserConfirmationTokenIsVerified(SplitToken::fromString(self::TOKEN_STRING), false)
            )
        );

        $this->expectException(EmailChangeConfirmationRejected::class);
        $handler(new ConfirmEmailAddressChange(self::TOKEN_STRING));
    }

    /** @test */
    public function it_handles_emailAddress_change_confirmation_with_no_result()
    {
        $handler = new ConfirmEmailAddressChangeHandler($this->expectUserNotSaved());

        $this->expectException(EmailChangeConfirmationRejected::class);
        $handler(new ConfirmEmailAddressChange(self::TOKEN_STRING));
    }

    private function expectUserConfirmationTokenIsVerified(SplitToken $token, bool $result = true): User
    {
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->confirmEmailAddressChange($token)->willReturn($result);

        return $userProphecy->reveal();
    }

    private function expectUserSaved(string $selector, User $user): UserCollection
    {
        $repositoryProphecy = $this->prophesize(UserCollection::class);
        $repositoryProphecy->getsByEmailAddressChangeToken($selector)->willReturn($user);
        $repositoryProphecy->save($user)->shouldBeCalledTimes(1);

        return $repositoryProphecy->reveal();
    }

    private function expectUserNotSaved(): UserCollection
    {
        $repositoryProphecy = $this->prophesize(UserCollection::class);
        $repositoryProphecy->getsByEmailAddressChangeToken(Argument::any())->willReturn(null);
        $repositoryProphecy->save(Argument::any())->shouldNotBeCalled();

        return $repositoryProphecy->reveal();
    }
}
