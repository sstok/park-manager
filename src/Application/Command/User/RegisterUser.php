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

final class RegisterUser
{
    public bool $requireNewPassword = false;

    /**
     * @param string $password A password hash (the password in plain-text)
     */
    public function __construct(
        public UserId $id,
        public EmailAddress $email,
        public string $displayName,
        public string $password
    ) {
    }

    /**
     * @param string $password A password hash (the password in plain-text)
     */
    public static function with(string $id, string $email, string $displayName, string $password): self
    {
        return new self(UserId::fromString($id), new EmailAddress($email), $displayName, $password);
    }

    public function requireNewPassword(): self
    {
        $this->requireNewPassword = true;

        return $this;
    }
}
