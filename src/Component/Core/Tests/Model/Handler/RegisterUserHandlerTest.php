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

namespace ParkManager\Component\Core\Tests\Model\Handler;

use ParkManager\Component\Core\Exception\AdministratorEmailAddressAlreadyInUse;
use ParkManager\Component\Core\Model\Administrator;
use ParkManager\Component\Core\Model\Command\RegisterAdministrator;
use ParkManager\Component\Core\Model\Handler\RegisterAdministratorHandler;
use ParkManager\Component\User\Model\User;
use ParkManager\Component\User\Model\UserCollection;
use ParkManager\Component\User\Model\UserId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @internal
 */
final class RegisterUserHandlerTest extends TestCase
{
    private const ID_NEW = '01dd5964-5426-11e7-be03-acbc32b58315';
    private const ID_EXISTING = 'a0816f44-6545-11e7-a234-acbc32b58315';

    /** @test */
    public function it_handles_registration_of_new_administrator()
    {
        $command = new RegisterAdministrator(self::ID_NEW, 'John@example.com', 'My', 'name', 'my-password');

        $handler = new RegisterAdministratorHandler($this->expectUserSaved($command));
        $handler($command);
    }

    /** @test */
    public function it_handles_registration_without_password()
    {
        $command = new RegisterAdministrator(self::ID_NEW, 'John@example.com', 'My', 'name', null);

        $handler = new RegisterAdministratorHandler($this->expectUserSaved($command));
        $handler($command);
    }

    /** @test */
    public function it_handles_registration_of_new_user_with_already_existing_email_address()
    {
        $handler = new RegisterAdministratorHandler($this->expectUserNotSaved('john@example.com'));

        $this->expectException(AdministratorEmailAddressAlreadyInUse::class);

        $handler(new RegisterAdministrator(self::ID_NEW, 'John@example.com', 'My', 'name', null));
    }

    private function existingId(): UserId
    {
        return UserId::fromString(self::ID_EXISTING);
    }

    private function expectUserSaved(RegisterAdministrator $command): UserCollection
    {
        $repository = $this->prophesize(UserCollection::class);
        $repository->findByEmailAddress(Argument::any())->willReturn(null);
        $repository->save(Argument::that(function (Administrator $user) use ($command) {
            self::assertTrue($command->id()->equals($user->id()));
            self::assertEquals($command->email(), $user->email());
            self::assertEquals(mb_strtolower($command->email()), $user->canonicalEmail());
            self::assertEquals($command->password(), $user->password());
            self::assertEquals($command->firstName(), $user->firstName());
            self::assertEquals($command->lastName(), $user->lastName());

            return true;
        }))->shouldBeCalled();

        return $repository->reveal();
    }

    private function expectUserNotSaved(string $email): UserCollection
    {
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->id()->willReturn($this->existingId());

        $repositoryProphecy = $this->prophesize(UserCollection::class);
        $repositoryProphecy->findByEmailAddress($email)->willReturn($userProphecy->reveal());
        $repositoryProphecy->save(Argument::any())->shouldNotBeCalled();

        return $repositoryProphecy->reveal();
    }
}
