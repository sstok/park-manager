<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain;

use Lifthill\Component\Common\Domain\Model\EmailAddress;
use Lifthill\Component\Common\Domain\ResultSet;
use Lifthill\Component\Common\Test\MockRepository;
use ParkManager\Domain\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Domain\User\Exception\EmailChangeConfirmationRejected;
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\User\UserRepository;

final class UserRepositoryMock implements UserRepository
{
    /** @use MockRepository<User> */
    use MockRepository;

    public const USER_ID1 = 'dba1f6a0-3c5e-4cc2-9d10-2b8ddf3ce605';

    /**
     * @return array<string, string|\Closure>
     */
    protected function getFieldsIndexMapping(): array
    {
        return [
            'email' => static fn (User $user) => $user->email->canonical,
            'passwordResetToken' => static function (User $user) {
                $token = $user->passwordResetToken;

                return $token !== null ? $token->selector() : null;
            },
            'emailChangeToken' => static function (User $user) {
                $token = $user->emailAddressChangeToken;

                return $token !== null ? $token->selector() : null;
            },
        ];
    }

    public static function createUser(string $email = 'janE@example.com', string $id = self::USER_ID1): User
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

    public function all(): ResultSet
    {
        return $this->mockDoGetAll();
    }

    public function getByPasswordResetToken(string $selector): User
    {
        try {
            return $this->mockDoGetByField('passwordResetToken', $selector);
        } catch (UserNotFound) {
            throw new PasswordResetTokenNotAccepted();
        }
    }

    public function getByEmailAddressChangeToken(string $selector): User
    {
        try {
            return $this->mockDoGetByField('emailChangeToken', $selector);
        } catch (UserNotFound) {
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

    protected function throwOnNotFound(mixed $key): void
    {
        throw new UserNotFound((string) $key);
    }
}
