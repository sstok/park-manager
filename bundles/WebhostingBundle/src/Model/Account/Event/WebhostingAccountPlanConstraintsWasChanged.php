<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\Account\Event;

use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;

final class WebhostingAccountPlanConstraintsWasChanged
{
    /**
     * READ-ONLY.
     *
     * @var WebhostingAccountId
     */
    public $account;

    /**
     * READ-ONLY.
     *
     * @var Constraints
     */
    public $constraints;

    public function __construct(WebhostingAccountId $id, Constraints $constraints)
    {
        $this->account = $id;
        $this->constraints = $constraints;
    }
}
