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

namespace ParkManager\Component\ServiceBus\MessageGuard;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class UnauthorizedException extends \RuntimeException
{
    private $messageName;

    public static function forMessage(object $messageName): self
    {
        $e = new static ('You are not authorized to access the resource "'.\get_class($messageName).'".');
        $e->messageName = $messageName;

        return $e;
    }

    public function getMessageName(): object
    {
        return $this->messageName;
    }
}
