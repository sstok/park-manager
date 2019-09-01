<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\Plan\Event;

use ParkManager\Bundle\WebhostingBundle\Model\Plan\Capabilities;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;

final class WebhostingPlanWasCreated
{
    private $planId;
    private $capabilities;

    public function __construct(WebhostingPlanId $id, Capabilities $capabilities)
    {
        $this->planId    = $id;
        $this->capabilities = $capabilities;
    }

    public function id(): WebhostingPlanId
    {
        return $this->planId;
    }

    public function capabilities(): Capabilities
    {
        return $this->capabilities;
    }
}
