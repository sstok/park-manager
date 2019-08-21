<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Domain\Client\Event;

use ParkManager\Bundle\CoreBundle\Domain\Client\ClientId;
use ParkManager\Bundle\CoreBundle\Domain\Shared\EmailAddress;
use Rollerworks\Component\SplitToken\SplitToken;

final class ClientEmailAddressChangeWasRequested
{
    /** @var ClientId */
    private $id;

    /** @var SplitToken */
    private $token;

    /** @var EmailAddress */
    private $newEmail;

    public function __construct(ClientId $id, SplitToken $token, EmailAddress $newEmail)
    {
        $this->id       = $id;
        $this->token    = $token;
        $this->newEmail = $newEmail;
    }

    public function id(): ClientId
    {
        return $this->id;
    }

    public function token(): SplitToken
    {
        return $this->token;
    }

    public function getNewEmail(): EmailAddress
    {
        return $this->newEmail;
    }
}
