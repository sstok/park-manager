<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Model\Client;

use ParkManager\Bundle\CoreBundle\Model\Client\Exception\ClientNotFound;
use ParkManager\Bundle\CoreBundle\Model\Client\Exception\EmailChangeConfirmationRejected;
use ParkManager\Bundle\CoreBundle\Model\Client\Exception\PasswordResetConfirmationRejected;
use ParkManager\Bundle\CoreBundle\Model\EmailAddress;

interface ClientRepository
{
    /**
     * @throws ClientNotFound When no client was found with the id
     */
    public function get(ClientId $id): Client;

    /**
     * @throws ClientNotFound When no client was found with the email
     */
    public function getByEmail(EmailAddress $email): Client;

    /**
     * @throws PasswordResetConfirmationRejected When no client was found with the token-selector
     */
    public function getByPasswordResetToken(string $selector): Client;

    /**
     * @throws EmailChangeConfirmationRejected When no client was found with the token-selector
     */
    public function getByEmailAddressChangeToken(string $selector): Client;

    public function save(Client $client): void;

    public function remove(Client $client): void;
}
