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
    /**
     * READ-ONLY.
     */
    public UserId $id;

    /**
     * READ-ONLY: The password in hash-encoded format or null
     * to disable password based authentication.
     */
    public ?string $password;

    /**
     * @param string|null $password The password in hash-encoded format or null
     *                              to disable password based authentication
     */
    public function __construct(string $id, ?string $password)
    {
        $this->id = UserId::fromString($id);
        $this->password = $password;
    }
}
