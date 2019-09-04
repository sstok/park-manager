<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Model\Administrator\Event;

use ParkManager\Bundle\CoreBundle\Model\Administrator\AdministratorId;
use ParkManager\Bundle\CoreBundle\Model\EmailAddress;

final class AdministratorWasRegistered
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
    public $name;

    public function __construct(AdministratorId $id, EmailAddress $email, string $name)
    {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
    }
}
