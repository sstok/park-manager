<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User\Exception;

final class CannotMakeUserSuperAdmin extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Cannot add role ROLE_SUPER_ADMIN if role ROLE_ADMIN is not granted.');
    }
}
