<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Organization;

use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\User\UserId;

final class RegisterOrganization
{
    public OrganizationId $id;
    public string $name;
    public UserId $creator;

    /**
     * @param UserId $creator Main creator user-id (or owner of the organization)
     */
    public function __construct(OrganizationId $id, string $name, UserId $creator)
    {
        $this->id = $id;
        $this->name = $name;
        $this->creator = $creator;
    }
}
