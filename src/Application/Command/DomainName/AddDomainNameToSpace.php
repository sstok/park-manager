<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\DomainName;

use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class AddDomainNameToSpace
{
    public DomainNamePair $name;
    public SpaceId $space;
    public bool $primary;

    public function __construct(DomainNamePair $name, SpaceId $space, bool $primary = false)
    {
        $this->name = $name;
        $this->space = $space;
        $this->primary = $primary;
    }
}
