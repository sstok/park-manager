<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\UserId;

final class RequestEmailAddressChange
{
    public function __construct(public UserId $id, public EmailAddress $email)
    {
    }

    public static function with(string $id, string $email): self
    {
        return new self(UserId::fromString($id), new EmailAddress($email));
    }

    public function email(): EmailAddress
    {
        return $this->email;
    }
}
