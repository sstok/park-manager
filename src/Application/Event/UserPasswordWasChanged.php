<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class UserPasswordWasChanged extends Event
{
    public string $id;
    public string $password;

    public function __construct(string $id, string $newPassword)
    {
        $this->id = $id;
        $this->password = $newPassword;
    }
}
