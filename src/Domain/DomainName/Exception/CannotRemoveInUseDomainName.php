<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName\Exception;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class CannotRemoveInUseDomainName extends UseDomainNameException
{
    protected function getInitMessage(DomainNamePair $domainName, SpaceId $current): string
    {
        return sprintf(
            'Domain name "%s" of Hosting Space %s cannot be removed as it is still used by the following entities:',
            $domainName->toString(),
            $current->toString()
        );
    }
}
