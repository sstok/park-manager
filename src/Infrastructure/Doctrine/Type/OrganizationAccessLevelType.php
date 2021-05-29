<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Type;

use ParkManager\Domain\Organization\AccessLevel;

final class OrganizationAccessLevelType extends EnumType
{
    public const NAME = 'park_manager_organization_access_level';
    public const OBJECT_CLASS = AccessLevel::class;
    public const ENUM_TYPE = 'int';
}
