<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\Plan\Event;

use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;

final class WebhostingPlanWasCreated
{
    private $planId;
    private $constraints;

    public function __construct(WebhostingPlanId $id, Constraints $constraints)
    {
        $this->planId    = $id;
        $this->constraints = $constraints;
    }

    public function id(): WebhostingPlanId
    {
        return $this->planId;
    }

    public function constraints(): Constraints
    {
        return $this->constraints;
    }
}
