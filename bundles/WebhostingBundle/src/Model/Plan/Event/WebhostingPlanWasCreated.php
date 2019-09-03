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
    /**
     * READ-ONLY.
     *
     * @var WebhostingPlanId
     */
    public $id;

    /**
     * READ-ONLY.
     *
     * @var Constraints
     */
    public $constraints;

    public function __construct(WebhostingPlanId $id, Constraints $constraints)
    {
        $this->id          = $id;
        $this->constraints = $constraints;
    }
}
