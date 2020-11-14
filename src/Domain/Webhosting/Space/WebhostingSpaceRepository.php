<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space;

use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\Exception\CannotRemoveActiveWebhostingSpace;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;

interface WebhostingSpaceRepository
{
    /**
     * @throws WebhostingSpaceNotFound
     */
    public function get(SpaceId $id): Space;

    /**
     * @return iterable<Space>
     */
    public function allWithAssignedPlan(PlanId $id): iterable;

    public function save(Space $space): void;

    /**
     * Remove a webhosting space registration from the repository.
     *
     * @throws CannotRemoveActiveWebhostingSpace When space is still active
     */
    public function remove(Space $space): void;
}
