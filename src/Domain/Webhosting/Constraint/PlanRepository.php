<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\Exception\PlanNotFound;

interface PlanRepository
{
    /**
     * @throws PlanNotFound
     */
    public function get(PlanId $id): Plan;

    public function save(Plan $plan): void;

    public function remove(Plan $plan): void;
}
