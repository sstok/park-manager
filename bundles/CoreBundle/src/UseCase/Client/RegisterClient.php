<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\UseCase\Client;

use ParkManager\Bundle\CoreBundle\Domain\Client\ClientId;
use ParkManager\Bundle\CoreBundle\Domain\Shared\EmailAddress;

final class RegisterClient
{
    /**
     * READ-ONLY.
     *
     * @var ClientId
     */
    public $id;

    /**
     * READ-ONLY.
     *
     * @var EmailAddress
     */
    public $primaryEmail;

    /**
     * READ-ONLY.
     *
     * @var string
     */
    public $displayName;

    /**
     * The account-authentication password-hash.
     *
     * READ-ONLY.
     *
     * @var string|null
     */
    public $password;

    public function __construct(ClientId $id, EmailAddress $primaryEmail, string $displayName, ?string $password = null)
    {
        $this->id           = $id;
        $this->primaryEmail = $primaryEmail;
        $this->displayName  = $displayName;
        $this->password     = $password;
    }
}
