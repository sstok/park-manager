<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\Account\Event;

use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;

final class WebhostingAccountWasMarkedForRemoval
{
    /**
     * READ-ONLY.
     *
     * @var WebhostingAccountId
     */
    public $account;

    public function __construct(WebhostingAccountId $id)
    {
        $this->account = $id;
    }
}
