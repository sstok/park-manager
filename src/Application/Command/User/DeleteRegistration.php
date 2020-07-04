<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\User\UserId;

final class DeleteRegistration
{
    private UserId $id;

    public function __construct(string $id)
    {
        $this->id = UserId::fromString($id);
    }

    public function id(): UserId
    {
        return $this->id;
    }
}
