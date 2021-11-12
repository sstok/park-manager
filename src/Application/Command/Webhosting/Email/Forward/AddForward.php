<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Forward;

use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Webhosting\Email\ForwardId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Validator\Constraints\EmailboxName;
use ParkManager\Infrastructure\Validator\Constraints\Webhosting\DomainNameOfSpace;

#[DomainNameOfSpace()]
final class AddForward
{
    /**
     * @param string|EmailAddress $destination string means a script path
     */
    public function __construct(
        public SpaceId $space,

        public ForwardId $id,

        #[EmailboxName()]
        public string $address,

        public DomainNameId | DomainNamePair $domainName,
        public string | EmailAddress $destination,
    ) {
    }
}
