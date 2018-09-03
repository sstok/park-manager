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

namespace ParkManager\Module\WebhostingModule\Domain\Package\Exception;

final class CapabilityNotRegistered extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Webhosting Package Capability with id "%s" is not registered.', $id));
    }

    public static function withName(string $name): self
    {
        if (!class_exists($name)) {
            return new self(sprintf('Webhosting Package Capability %s cannot be found.', $name));
        }

        return new self(sprintf('Webhosting Package Capability %s is not registered.', $name));
    }
}
