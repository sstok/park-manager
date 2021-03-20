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
use ParkManager\Domain\OwnerId;

final class AddDomainName
{
    public DomainNameId $id;
    public OwnerId $owner;
    public DomainNamePair $name;

    public function __construct(DomainNameId $id, OwnerId $owner, DomainNamePair $name)
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->name = $name;
    }

    public static function with(string $id, string $ownerId, string $name, string $tld): self
    {
        return new self(DomainNameId::fromString($id), OwnerId::fromString($ownerId), new DomainNamePair($name, $tld));
    }
}
