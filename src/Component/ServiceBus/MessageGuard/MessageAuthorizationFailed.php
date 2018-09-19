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

namespace ParkManager\Component\ServiceBus\MessageGuard;

use function get_class;

final class MessageAuthorizationFailed extends \RuntimeException
{
    private $messageName;

    public static function forMessage(object $messageName): self
    {
        $e = new static('You are not authorized to access the resource "' . get_class($messageName) . '".');

        $e->messageName = $messageName;

        return $e;
    }

    public function getMessageName(): object
    {
        return $this->messageName;
    }
}
