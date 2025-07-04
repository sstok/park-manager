<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User\Exception;

use Lifthill\Component\Common\Domain\Model\EmailAddress;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\User\UserId;

final class EmailAddressAlreadyInUse extends \InvalidArgumentException implements DomainError
{
    public UserId $id;
    public EmailAddress $address;

    public function __construct(UserId $id, EmailAddress $address)
    {
        parent::__construct(
            \sprintf(
                'The email address "%s" is already in use by user with id "%s".',
                $address->toString(),
                $id->toString()
            )
        );

        $this->id = $id;
        $this->address = $address;
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage('email_address_already_in_use', [
            'id' => new EntityLink($this->id),
            'email' => $this->address,
        ], 'validators');
    }

    public function getPublicMessage(): string
    {
        return 'The email address "{address}" is already in use by user "{id}"';
    }
}
