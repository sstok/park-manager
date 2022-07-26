<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Organization;

use ParkManager\Domain\EnumEqualityTrait;

enum AccessLevel: int
{
    use EnumEqualityTrait;

    /**
     * Has access to manage the organization details and members.
     */
    case LEVEL_MANAGER = 1;

    /**
     * Has only (restricted) access to organization owned Spaces.
     */
    case LEVEL_COLLABORATOR = 2;
}
