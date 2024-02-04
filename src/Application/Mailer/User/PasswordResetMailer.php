<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Mailer\User;

use Lifthill\Component\Common\Domain\Model\EmailAddress;
use Rollerworks\Component\SplitToken\SplitToken;

interface PasswordResetMailer
{
    public function send(EmailAddress $emailAddress, SplitToken $splitToken): void;
}
