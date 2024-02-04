<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Messenger;

use Lifthill\Component\Common\Domain\ResultSet;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\Webhosting\Space\Space;

interface DomainNameSpaceUsageValidator
{
    /**
     * @return array<class-string, ResultSet<mixed>> ["EntityName" => {ResultSet<EntityName>}]
     */
    public function __invoke(DomainName $domainName, Space $space): array;
}
