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

use ParkManager\Component\User\Exception\UserNotFound;
use ParkManager\Component\User\Model\UserCollection;

/**
 * @internal
 */
trait UserCommandHandlerMissingUserTrait
{
    abstract public function it_fails_for_not_existing_user();

    protected function expectUserNotFoundWith(\Closure $handlerCreator, object $command): void
    {
        $repositoryProphecy = $this->prophesize(UserCollection::class);
        $repositoryProphecy->getById($command->id())->willReturn(null);
        $handler = $handlerCreator($repositoryProphecy->reveal());

        $this->expectException(UserNotFound::class);
        $this->expectExceptionMessage(UserNotFound::withUserId($command->id())->getMessage());

        $handler($command);
    }
}
