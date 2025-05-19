<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Ftp\User;

use ParagonIE\HiddenString\HiddenString;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;

final class ChangeFtpUserPassword
{
    /**
     * @param HiddenString $password Password in plain format
     */
    public function __construct(
        public FtpUserId $id,
        public HiddenString $password,
    ) {
    }
}
