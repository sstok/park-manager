<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Ftp\User;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Infrastructure\Validator\Constraints\ValidUsername;
use ParkManager\Infrastructure\Validator\Constraints\Webhosting\DomainNameOfSpace;

#[DomainNameOfSpace(spaceProperty: '@id.space')]
final class ChangeFtpUserUsername
{
    public function __construct(
        public FtpUserId $id,

        #[ValidUsername()]
        public string $username,

        public null | DomainNameId | DomainNamePair $domainName = null,
    ) {}
}
