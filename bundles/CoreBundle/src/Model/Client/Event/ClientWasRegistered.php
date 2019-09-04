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

final class ClientWasRegistered
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
    public $email;

    /**
     * READ-ONLY.
     *
     * @var string
     */
    public $displayName;

    public function __construct(ClientId $id, EmailAddress $email, string $displayName)
    {
        $this->id = $id;
        $this->email = $email;
        $this->displayName = $displayName;
    }
}
