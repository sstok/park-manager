<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\DomainName;

use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\User\UserId;

final class AddDomainName
{
    public DomainNameId $id;
    public ?UserId $user;
    public DomainNamePair $name;

    public function __construct(DomainNameId $id, ?UserId $user, DomainNamePair $name)
    {
        $this->id = $id;
        $this->user = $user;
        $this->name = $name;
    }

    public static function with(string $id, ?string $userId, string $name, string $tld): self
    {
        return new self(DomainNameId::fromString($id), $userId ? UserId::fromString($userId) : null, new DomainNamePair($name, $tld));
    }
}
