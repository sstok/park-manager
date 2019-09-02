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
    private $id;
    private $constraints;

    public function __construct(WebhostingAccountId $id, Constraints $constraints)
    {
        $this->id           = $id;
        $this->constraints = $constraints;
    }

    public function id(): WebhostingAccountId
    {
        return $this->id;
    }

    public function constraints(): Constraints
    {
        return $this->constraints;
    }
}
