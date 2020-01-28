<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain;

use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\User\UserRepository;
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\Exception\EmailChangeConfirmationRejected;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Tests\Mock\Domain\MockRepository;

final class UserRepositoryMock implements UserRepository
{
    use MockRepository;

    public const USER_ID1 = '01dd5964-5426-11e7-be03-acbc32b58315';

    protected function getFieldsIndexMapping(): array
    {
        return [
            'email' => static function (User $user) {
                return $user->getEmail()->canonical;
            },
            'passwordResetToken' => static function (User $user) {
                $token = $user->getPasswordResetToken();

                return $token !== null ? $token->selector() : null;
            },
            'emailChangeToken' => static function (User $user) {
                $token = $user->getEmailAddressChangeToken();

                return $token !== null ? $token->selector() : null;
            },
        ];
    }

    public static function createUser($email = 'janE@example.com', $id = self::USER_ID1): User
    {
        return User::register(UserId::fromString($id), new EmailAddress($email), 'J', 'nope');
    }

    public function get(UserId $id): User
    {
        return $this->mockDoGetById($id);
    }

    public function getByEmail(EmailAddress $email): User
    {
        return $this->mockDoGetByField('email', $email->canonical);
    }

    public function getByPasswordResetToken(string $selector): User
    {
        try {
            return $this->mockDoGetByField('passwordResetToken', $selector);
        } catch (UserNotFound $e) {
            throw new PasswordResetTokenNotAccepted();
        }
    }

    public function getByEmailAddressChangeToken(string $selector): User
    {
        try {
            return $this->mockDoGetByField('emailChangeToken', $selector);
        } catch (UserNotFound $e) {
            throw new EmailChangeConfirmationRejected();
        }
    }

    public function save(User $administrator): void
    {
        $this->mockDoSave($administrator);
    }

    public function remove(User $administrator): void
    {
        $this->mockDoRemove($administrator);
    }

    protected function throwOnNotFound($key): void
    {
        throw new UserNotFound((string) $key);
    }
}
