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

use ParkManager\Component\User\Model\User;
use ParkManager\Component\User\Model\UserCollection;
use ParkManager\Component\User\Model\UserId;

/**
 * @internal
 */
trait UserCommandHandlerRepositoryTrait
{
    protected static $userId = '45a8ce38-5405-11e7-8853-acbc32b58315';

    protected function expectUserModelMethodCallAndSave(string $method, ...$arg): UserCollection
    {
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->id()->willReturn(UserId::fromString(self::$userId));
        $userProphecy->$method(...$arg)->shouldBeCalled();
        $user = $userProphecy->reveal();

        $repository = $this->prophesize(UserCollection::class);
        $repository->get($user->id())->willReturn($user);
        $repository->save($user)->shouldBeCalled();

        return $repository->reveal();
    }
}
