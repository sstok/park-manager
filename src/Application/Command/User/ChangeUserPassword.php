<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\User\UserId;

final class ChangeUserPassword
{
    public UserId $id;

    /**
     * @param string $password The password in hashed format
     */
    public function __construct(
        string | UserId $id,
        public string $password,
        public bool $temporary = false
    ) {
        if (\is_string($id)) {
            $id = UserId::fromString($id);
        }

        $this->id = $id;
    }
}
