<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain;

use ParkManager\Domain\Administrator\Administrator;
use ParkManager\Domain\Administrator\AdministratorId;
use ParkManager\Domain\Administrator\AdministratorRepository;
use ParkManager\Domain\Administrator\Exception\AdministratorNotFound;
use ParkManager\Domain\Administrator\Exception\PasswordResetConfirmationRejected;
use ParkManager\Domain\EmailAddress;
use ParkManager\Tests\Mock\Domain\MockRepository;

final class AdministratorRepositoryMock implements AdministratorRepository
{
    use MockRepository;

    public const USER_ID1 = '126fb452-0a96-11e9-abf1-acbc32b58315';

    protected function getFieldsIndexMapping(): array
    {
        return [
            'email' => static function (Administrator $administrator) {
                return $administrator->getEmailAddress()->canonical;
            },
            'passwordResetToken' => static function (Administrator $administrator) {
                $token = $administrator->getPasswordResetToken();

                return $token !== null ? $token->selector() : null;
            },
        ];
    }

    protected function throwOnNotFound($key): void
    {
        throw new AdministratorNotFound((string) $key);
    }

    public static function createAdministrator($email = 'janE@example.com', $id = self::USER_ID1): Administrator
    {
        return Administrator::register(AdministratorId::fromString($id), new EmailAddress($email), 'J', 'nope');
    }

    public function get(AdministratorId $id): Administrator
    {
        return $this->mockDoGetById($id);
    }

    public function getByEmail(EmailAddress $email): Administrator
    {
        return $this->mockDoGetByField('email', $email->canonical);
    }

    public function getByPasswordResetToken(string $selector): Administrator
    {
        try {
            return $this->mockDoGetByField('passwordResetToken', $selector);
        } catch (AdministratorNotFound $e) {
            throw new PasswordResetConfirmationRejected();
        }
    }

    public function save(Administrator $administrator): void
    {
        $this->mockDoSave($administrator);
    }

    public function remove(Administrator $administrator): void
    {
        $this->mockDoRemove($administrator);
    }
}
