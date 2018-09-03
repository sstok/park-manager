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

namespace ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Handler;

use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\RegisterUser;

/**
 * @internal
 */
final class RegisterUserHandler
{
    public function __invoke(RegisterUser $command): void
    {
    }
}
