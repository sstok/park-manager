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
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;

final class WebhostingAccountPlanAssignmentWasChanged
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
     * @var WebhostingPlanId
     */
    public $plan;

    /**
     * READ-ONLY.
     *
     * @var Constraints|null
     */
    public $planConstraints;

    public function __construct(WebhostingAccountId $id, WebhostingPlan $plan)
    {
        $this->account = $id;
        $this->plan = $plan->getId();
    }

    public static function withConstraints(WebhostingAccountId $id, WebhostingPlan $plan): self
    {
        $event = new self($id, $plan);
        $event->planConstraints = $plan->getConstraints();

        return $event;
    }
}
