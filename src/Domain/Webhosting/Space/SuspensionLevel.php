<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space;

use ParkManager\Domain\EnumEqualityTrait;

/**
 * When a Space is marked as Suspended it's still accessible but in read-only
 * modes. Depending on the Suspension level, FTP might still be accessible.
 */
enum SuspensionLevel: int
{
    use EnumEqualityTrait;

    /**
     * Access limited; either compromised, FTP and mail is accessible.
     */
    case ACCESS_LIMITED = 1;

    /**
     * Access restricted; data is READ only, FTP and mail access is disabled.
     */
    case ACCESS_RESTRICTED = 2;

    /**
     * Locked (either payment pending or deletion in process).
     *
     * Nothing is accessible. The website is not reachable and
     * shows a generic message to the visitor.
     */
    case LOCKED = 3;
}
