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

namespace ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Guard;

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
