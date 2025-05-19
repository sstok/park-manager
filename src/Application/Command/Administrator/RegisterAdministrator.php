<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Administrator;

use Lifthill\Component\Common\Domain\Model\EmailAddress;
use ParkManager\Domain\User\UserId;

final class RegisterAdministrator
{
    public bool $requireNewPassword = false;
    public bool $superAdmin = false;

    /**
     * @param string $password An encoded password string (not plain)
     */
    public function __construct(
        public UserId $id,
        public EmailAddress $email,
        public string $displayName,
        public string $password
    ) {
    }

    /**
     * @param string $password An encoded password string (not plain)
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

    public function asSuperAdmin(): self
    {
        $this->superAdmin = true;

        return $this;
    }
}
