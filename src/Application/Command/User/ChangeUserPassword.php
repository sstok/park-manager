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

    public function id(): UserId
    {
        return $this->id;
    }

    /**
     * @return string|null The password in hash-encoded format or null
     *                     to disable password based authentication
     */
    public function password(): ?string
    {
        return $this->password;
    }
}
