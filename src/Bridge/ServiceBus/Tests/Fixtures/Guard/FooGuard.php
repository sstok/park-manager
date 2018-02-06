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

namespace ParkManager\Bridge\ServiceBus\Tests\Fixtures\Guard;

use ParkManager\Component\ServiceBus\MessageGuard\PermissionGuard;

/**
 * @internal
 */
final class FooGuard implements PermissionGuard
{
    public function decide(object $message): int
    {
        return self::PERMISSION_ABSTAIN;
    }
}
