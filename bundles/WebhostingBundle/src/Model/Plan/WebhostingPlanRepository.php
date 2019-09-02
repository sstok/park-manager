<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\Plan;

use ParkManager\Bundle\WebhostingBundle\Model\Plan\Exception\WebhostingPlanNotFound;

interface WebhostingPlanRepository
{
    /**
     * @throws WebhostingPlanNotFound When no plan was found with the id
     */
    public function get(WebhostingPlanId $id): WebhostingPlan;

    /**
     * Save the WebhostingPlan in the repository.
     *
     * This will either store a new plan or update an existing one.
     */
    public function save(WebhostingPlan $plan): void;

    /**
     * Remove an webhosting plan from the repository.
     */
    public function remove(WebhostingPlan $plan): void;
}
