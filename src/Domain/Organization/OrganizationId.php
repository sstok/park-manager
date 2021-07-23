<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Organization;

use ParkManager\Domain\UniqueIdentity;
use ParkManager\Domain\UuidTrait;

final class OrganizationId implements UniqueIdentity
{
    /**
     * Administrator Organization (internal).
     */
    public const ADMIN_ORG = '35b7d14c-d807-490e-9608-41633723458d';

    use UuidTrait;
}
