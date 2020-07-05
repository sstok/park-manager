<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\Webhosting;

use ParkManager\Domain\Webhosting\Constraint\Exception\PlanNotFound;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Constraint\PlanRepository;
use ParkManager\Tests\Mock\Domain\MockRepository;

/** @internal */
final class PlanRepositoryMock implements PlanRepository
{
    /** @use MockRepository<Plan> */
    use MockRepository;

    public function get(PlanId $id): Plan
    {
        return $this->mockDoGetById($id);
    }

    public function save(Plan $plan): void
    {
        $this->mockDoSave($plan);
    }

    public function remove(Plan $plan): void
    {
        $this->mockDoRemove($plan);
    }

    protected function throwOnNotFound($key): void
    {
        throw new PlanNotFound($key);
    }
}
