<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName\Exception;

use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Exception\NotFoundException;

final class DomainNameNotFound extends NotFoundException
{
    public static function withId(DomainNameId $id): self
    {
        return new self(sprintf('Domain-name with id "%s" does not exist.', $id->toString()));
    }

    public static function withName(DomainNamePair $fullName): self
    {
        return new self(sprintf('Domain-name with name "%s" does not exist.', $fullName->toString()));
    }
}
