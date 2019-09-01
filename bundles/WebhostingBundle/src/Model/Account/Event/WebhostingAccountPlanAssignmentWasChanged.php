<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\Account\Event;

use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Capabilities;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;

final class WebhostingAccountPlanAssignmentWasChanged
{
    private $accountId;
    private $plan;

    /** @var Capabilities|null */
    private $capabilities;

    public function __construct(WebhostingAccountId $id, WebhostingPlan $plan)
    {
        $this->accountId = $id;
        $this->plan   = $plan->id();
    }

    public static function withCapabilities(WebhostingAccountId $id, WebhostingPlan $plan): self
    {
        $event               = new self($id, $plan);
        $event->capabilities = $plan->capabilities();

        return $event;
    }

    public function id(): WebhostingAccountId
    {
        return $this->accountId;
    }

    public function plan(): WebhostingPlanId
    {
        return $this->plan;
    }

    public function capabilities(): ?Capabilities
    {
        return $this->capabilities;
    }
}
