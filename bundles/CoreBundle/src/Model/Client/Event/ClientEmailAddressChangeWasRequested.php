<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Model\Client\Event;

use ParkManager\Bundle\CoreBundle\Model\Client\ClientId;
use ParkManager\Bundle\CoreBundle\Model\EmailAddress;
use Rollerworks\Component\SplitToken\SplitToken;

final class ClientEmailAddressChangeWasRequested
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
     * @var SplitToken
     */
    public $token;

    /**
     * READ-ONLY.
     *
     * @var EmailAddress
     */
    public $newEmail;

    public function __construct(ClientId $id, SplitToken $token, EmailAddress $newEmail)
    {
        $this->id = $id;
        $this->token = $token;
        $this->newEmail = $newEmail;
    }
}
