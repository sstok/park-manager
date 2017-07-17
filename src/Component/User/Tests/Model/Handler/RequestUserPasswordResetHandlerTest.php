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
use ParkManager\Component\User\Canonicalizer\SimpleEmailCanonicalizer;
use ParkManager\Component\User\Model\Command\RequestUserPasswordReset;
use ParkManager\Component\User\Model\Handler\RequestUserPasswordResetHandler;
use ParkManager\Component\User\Model\Service\PasswordResetMailer;
use ParkManager\Component\User\Model\User;
use ParkManager\Component\User\Model\UserCollection;
use ParkManager\Component\User\Model\UserId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @internal
 */
final class RequestUserPasswordResetHandlerTest extends TestCase
{
    private const USER_ID = '01dd5964-5426-11e7-be03-acbc32b58315';

    /** @test */
    public function it_handles_password_reset_request()
    {
        $handler = new RequestUserPasswordResetHandler(
            $this->expectUserSaved('john2@example.com', $this->expectUserConfirmationTokenIsSet()),
            new SimpleEmailCanonicalizer(),
            $this->createConfirmationMailer('John2@example.com')
        );

        $command = new RequestUserPasswordReset('John2@example.com');
        $handler($command);
    }

    /** @test */
    public function it_handles_password_reset_request_with_token_already_set()
    {
        $handler = new RequestUserPasswordResetHandler(
            $this->expectUserNotSaved('john2@example.com', $this->expectUserConfirmationTokenIsNotSet()),
            new SimpleEmailCanonicalizer(),
            $this->createConfirmationMailer(null)
        );

        $command = new RequestUserPasswordReset('John2@example.com');
        $handler($command);
    }

    /** @test */
    public function it_handles_password_reset_request_with_no_existing_emailAddress()
    {
        $handler = new RequestUserPasswordResetHandler(
            $this->expectUserNotSaved('john2@example.com', null),
            new SimpleEmailCanonicalizer(),
            $this->createConfirmationMailer(null)
        );

        $command = new RequestUserPasswordReset('John2@example.com');
        $handler($command);
    }

    private function existingId(): UserId
    {
        return UserId::fromString(self::USER_ID);
    }

    private function expectUserConfirmationTokenIsSet(): User
    {
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->id()->willReturn($this->existingId());
        $userProphecy->setPasswordResetToken(Argument::any())->willReturn(true);

        return $userProphecy->reveal();
    }

    private function expectUserConfirmationTokenIsNotSet(): User
    {
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->id()->willReturn($this->existingId());
        $userProphecy->setPasswordResetToken(Argument::any())->willReturn(false);

        return $userProphecy->reveal();
    }

    private function expectUserNotSaved(string $email, ?User $user): UserCollection
    {
        $repositoryProphecy = $this->prophesize(UserCollection::class);
        $repositoryProphecy->getByEmailAddress($email)->willReturn($user);
        $repositoryProphecy->save(Argument::any())->shouldNotBeCalled();

        return $repositoryProphecy->reveal();
    }

    private function expectUserSaved(string $email, User $user): UserCollection
    {
        $repositoryProphecy = $this->prophesize(UserCollection::class);
        $repositoryProphecy->getByEmailAddress($email)->willReturn($user);
        $repositoryProphecy->save($user)->shouldBeCalledTimes(1);

        return $repositoryProphecy->reveal();
    }

    private function createConfirmationMailer(?string $email): PasswordResetMailer
    {
        $resetMailerProphecy = $this->prophesize(PasswordResetMailer::class);

        if ($email) {
            $resetMailerProphecy->send(
                $email,
                Argument::that(
                    function (SplitToken $splitToken) {
                        return '' !== $splitToken->token();
                    }
                ),
                Argument::any()
            )->shouldBeCalledTimes(1);
        } else {
            $resetMailerProphecy->send(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        }

        return $resetMailerProphecy->reveal();
    }
}
