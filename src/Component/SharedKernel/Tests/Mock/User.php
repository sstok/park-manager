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

namespace ParkManager\Component\SharedKernel\Tests\Mock;

use ParkManager\Component\SharedKernel\Event\EventsRecordingEntity;
use ParkManager\Component\SharedKernel\Tests\Mock\Event\UserWasRegistered;

final class User extends EventsRecordingEntity
{
    /**
     * @var string
     */
    private $name;

    public static function register(string $name): self
    {
        $instance = new self();
        $instance->recordThat(new UserWasRegistered(1, $name));
        $instance->name = $name;

        return $instance;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function changeName(string $newName)
    {
        $this->name = $newName;
    }

    public function id()
    {
        return 1;
    }
}
