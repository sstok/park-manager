<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Organization;

use ParkManager\Domain\Organization\OrganizationId;

final class RemoveOrganization
{
    public function __construct(public OrganizationId $id)
    {
    }
}
