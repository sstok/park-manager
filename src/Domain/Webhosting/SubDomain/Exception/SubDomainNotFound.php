<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\SubDomain\Exception;

use ParkManager\Domain\Webhosting\SubDomain\SubDomainNameId;
use ParkManager\Domain\Exception\NotFoundException;

final class SubDomainNotFound extends NotFoundException
{
    public static function withId(SubDomainNameId $id): self
    {
        return new self(\sprintf('SubDomain with id "%s" does not exist.', $id->toString()));
    }
}
