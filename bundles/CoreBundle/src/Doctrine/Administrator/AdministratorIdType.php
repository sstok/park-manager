<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Doctrine\Administrator;

use ParkManager\Bundle\CoreBundle\Doctrine\DomainIdType;
use ParkManager\Bundle\CoreBundle\Model\Administrator\AdministratorId;

final class AdministratorIdType extends DomainIdType
{
    public const NAME = 'park_manager_administrator_id';
    public const OBJECT_CLASS = AdministratorId::class;
}
