<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User\Exception;

use InvalidArgumentException;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\TranslatableException;
use ParkManager\Domain\User\UserId;

final class EmailAddressAlreadyInUse extends InvalidArgumentException implements TranslatableException
{
    public UserId $id;
    public EmailAddress $address;

    public function __construct(UserId $id, EmailAddress $address)
    {
        $this->id = $id;
        $this->address = $address;
    }

    public function getTranslatorId(): string
    {
        return 'email_address_already_in_use';
    }

    public function getTranslationArgs(): array
    {
        return [
            'id' => $this->id->toString(),
            'email' => $this->address->toString(),
        ];
    }
}
