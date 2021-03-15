<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Organization;

use ParkManager\Domain\ResultSet;
use ParkManager\Domain\User\UserId;

interface OrganizationRepository
{
    public function get(OrganizationId $id): Organization;

    /**
     * @return ResultSet<Organization>
     */
    public function all(): ResultSet;

    /**
     * @return ResultSet<Organization>
     */
    public function allAccessibleBy(UserId $userId): ResultSet;

    public function save(Organization $organization): void;

    public function remove(Organization $organization): void;
}
