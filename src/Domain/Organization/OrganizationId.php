<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Organization;

use Lifthill\Component\Common\Domain\UniqueIdentity;
use Lifthill\Component\Common\Domain\UuidTrait;

final class OrganizationId implements UniqueIdentity
{
    /**
     * Administrator Organization (internal).
     *
     * All administrators MUST be part of this organization.
     */
    public const ADMIN_ORG = '35b7d14c-d807-490e-9608-41633723458d';

    /**
     * SystemApplication organization (internal).
     *
     * Cannot be assigned as owner trough the UI, and has no actual members.
     * This is an owner-only organization used for internally managed (webhosting) Spaces,
     * like the application itself and reseller entry domain-names and routing.
     */
    public const SYSTEM_APP = '6d24366b-6f2e-484e-87e6-4b04188922fc';

    use UuidTrait;
}
