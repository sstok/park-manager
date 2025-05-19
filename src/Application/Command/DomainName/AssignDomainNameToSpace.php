<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\DomainName;

use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class AssignDomainNameToSpace
{
    public function __construct(
        public DomainNameId $id,
        public SpaceId $space,
        public bool $primary = false
    ) {
    }

    public static function with(string $id, string $spaceId, bool $primary = false): self
    {
        return new self(DomainNameId::fromString($id), SpaceId::fromString($spaceId), $primary);
    }
}
