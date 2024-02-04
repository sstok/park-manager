<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User;

use Lifthill\Component\Common\Domain\Attribute\Repository;
use Lifthill\Component\Common\Domain\Model\EmailAddress;
use Lifthill\Component\Common\Domain\ResultSet;
use ParkManager\Domain\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Domain\User\Exception\EmailChangeConfirmationRejected;
use ParkManager\Domain\User\Exception\UserNotFound;

#[Repository]
interface UserRepository
{
    /**
     * @throws UserNotFound
     */
    public function get(UserId $id): User;

    /**
     * @throws UserNotFound
     */
    public function getByEmail(EmailAddress $email): User;

    /**
     * @return ResultSet<User>
     */
    public function all(): ResultSet;

    /**
     * @throws PasswordResetTokenNotAccepted When no user was found with the token-selector
     */
    public function getByPasswordResetToken(string $selector): User;

    /**
     * @throws EmailChangeConfirmationRejected When no user was found with the token-selector
     */
    public function getByEmailAddressChangeToken(string $selector): User;

    public function save(User $user): void;

    public function remove(User $user): void;
}
