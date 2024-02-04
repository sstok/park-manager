<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\SubDomain;

use ParkManager\Domain\Webhosting\SubDomain\SubDomainNameId;

final class RemoveSubDomain
{
    public function __construct(public SubDomainNameId $id) {}

    public static function with(string $id): self
    {
        return new self(SubDomainNameId::fromString($id));
    }
}
