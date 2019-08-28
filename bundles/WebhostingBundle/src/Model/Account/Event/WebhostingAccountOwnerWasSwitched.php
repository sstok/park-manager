<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\Account\Event;

use ParkManager\Bundle\CoreBundle\Model\OwnerId;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;

final class WebhostingAccountOwnerWasSwitched
{
    private $id;
    private $oldOwner;
    private $newOwner;

    public function __construct(WebhostingAccountId $id, OwnerId $oldOwner, OwnerId $newOwner)
    {
        $this->newOwner = $newOwner;
        $this->oldOwner = $oldOwner;
        $this->id       = $id;
    }

    public function id(): WebhostingAccountId
    {
        return $this->id;
    }

    public function oldOwner(): OwnerId
    {
        return $this->oldOwner;
    }

    public function newOwner(): OwnerId
    {
        return $this->newOwner;
    }
}
