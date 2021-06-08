<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\User\UserId;

final class RevokeUserRole
{
    /**
     * @var array<int, string>
     */
    public array $roles;

    public function __construct(
        public UserId $id,
        string ...$roles
    ) {
        $this->roles = $roles;
    }

    public static function with(string $id, string ...$roles): self
    {
        return new self(UserId::fromString($id), ...$roles);
    }
}
