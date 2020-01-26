<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Administrator;

use ParkManager\Domain\Administrator\AdministratorId;
use ParkManager\Domain\EmailAddress;

final class RegisterAdministrator
{
    /**
     * READ-ONLY.
     *
     * @var AdministratorId
     */
    public $id;

    /**
     * READ-ONLY.
     *
     * @var EmailAddress
     */
    public $email;

    /**
     * READ-ONLY.
     *
     * @var string
     */
    public $displayName;

    /**
     * READ-ONLY.
     *
     * @var string|null
     */
    public $password;

    /**
     * @param string|null $password Null (no password) or an encoded password string (not plain)
     */
    public function __construct(string $id, string $email, string $displayName, ?string $password = null)
    {
        $this->id = AdministratorId::fromString($id);
        $this->email = new EmailAddress($email);
        $this->displayName = $displayName;
        $this->password = $password;
    }
}
