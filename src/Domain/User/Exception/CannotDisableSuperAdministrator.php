<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User\Exception;

use InvalidArgumentException;
use ParkManager\Domain\User\UserId;

final class CannotDisableSuperAdministrator extends InvalidArgumentException
{
    public UserId $id;

    public function __construct(UserId $id)
    {
        parent::__construct(sprintf('User with id "%s" is a SuperAdmin cannot have their access disabled.', $id->toString()));

        $this->id = $id;
    }
}
