<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Ftp\User;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParagonIE\HiddenString\HiddenString;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Validator\Constraints\DirectoryPath;
use ParkManager\Infrastructure\Validator\Constraints\ValidUsername;
use ParkManager\Infrastructure\Validator\Constraints\Webhosting\DomainNameOfSpace;

#[DomainNameOfSpace()]
final class RegisterFtpUser
{
    /**
     * @param HiddenString $password Password in plain format
     * @param string|null  $homeDir  Home-directory. Optional if not set uses the webhosting root-directory.
     */
    public function __construct(
        public FtpUserId $id,
        public SpaceId $space,

        #[ValidUsername()]
        public string $username,

        public DomainNameId | DomainNamePair $domainName,
        public HiddenString $password,

        #[DirectoryPath]
        public ?string $homeDir = null,
    ) {
    }
}
