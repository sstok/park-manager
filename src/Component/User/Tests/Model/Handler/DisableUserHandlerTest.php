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

use ParkManager\Component\User\Model\Command\DisableUser;
use ParkManager\Component\User\Model\Handler\DisableUserHandler;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DisableUserHandlerTest extends TestCase
{
    use UserCommandHandlerRepositoryTrait;

    /** @test */
    public function it_disables_user_access()
    {
        $handler = new DisableUserHandler($this->expectUserModelMethodCallAndSave('disable'));

        $command = new DisableUser(self::$userId);
        $handler($command);
    }
}
