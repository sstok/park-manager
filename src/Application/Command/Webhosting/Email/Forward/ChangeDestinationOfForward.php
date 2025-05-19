<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Forward;

use Lifthill\Component\Common\Domain\Model\EmailAddress;
use ParkManager\Domain\Webhosting\Email\ForwardId;

final class ChangeDestinationOfForward
{
    /**
     * @param string|EmailAddress $destination string means a script path
     */
    public function __construct(
        public ForwardId $id,
        public EmailAddress | string $destination,
    ) {
    }
}
