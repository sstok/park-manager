<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain;

use ParkManager\Domain\Client\Client;
use ParkManager\Domain\Client\ClientId;
use ParkManager\Domain\Client\ClientRepository;
use ParkManager\Domain\Client\Exception\ClientNotFound;
use ParkManager\Domain\Client\Exception\EmailChangeConfirmationRejected;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Tests\Mock\Domain\MockRepository;

final class ClientRepositoryMock implements ClientRepository
{
    use MockRepository;

    public const USER_ID1 = '01dd5964-5426-11e7-be03-acbc32b58315';

    protected function getFieldsIndexMapping(): array
    {
        return [
            'email' => static function (Client $client) {
                return $client->getEmail()->canonical;
            },
            'passwordResetToken' => static function (Client $client) {
                $token = $client->getPasswordResetToken();

                return $token !== null ? $token->selector() : null;
            },
            'emailChangeToken' => static function (Client $client) {
                $token = $client->getEmailAddressChangeToken();

                return $token !== null ? $token->selector() : null;
            },
        ];
    }

    public static function createClient($email = 'janE@example.com', $id = self::USER_ID1): Client
    {
        return Client::register(ClientId::fromString($id), new EmailAddress($email), 'J', 'nope');
    }

    public function get(ClientId $id): Client
    {
        return $this->mockDoGetById($id);
    }

    public function getByEmail(EmailAddress $email): Client
    {
        return $this->mockDoGetByField('email', $email->canonical);
    }

    public function getByPasswordResetToken(string $selector): Client
    {
        try {
            return $this->mockDoGetByField('passwordResetToken', $selector);
        } catch (ClientNotFound $e) {
            throw new PasswordResetTokenNotAccepted();
        }
    }

    public function getByEmailAddressChangeToken(string $selector): Client
    {
        try {
            return $this->mockDoGetByField('emailChangeToken', $selector);
        } catch (ClientNotFound $e) {
            throw new EmailChangeConfirmationRejected();
        }
    }

    public function save(Client $administrator): void
    {
        $this->mockDoSave($administrator);
    }

    public function remove(Client $administrator): void
    {
        $this->mockDoRemove($administrator);
    }

    protected function throwOnNotFound($key): void
    {
        throw new ClientNotFound((string) $key);
    }
}
