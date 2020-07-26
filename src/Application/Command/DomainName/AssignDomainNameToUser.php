<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\DomainName;

use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\User\UserId;

final class AssignDomainNameToUser
{
    public DomainNameId $id;
    private UserId $user;

    public function __construct(DomainNameId $id, UserId $user)
    {
        $this->id = $id;
        $this->user = $user;
    }

    public static function with(string $id, ?string $userId): self
    {
        return new self(DomainNameId::fromString($id), UserId::fromString($userId));
    }
}
