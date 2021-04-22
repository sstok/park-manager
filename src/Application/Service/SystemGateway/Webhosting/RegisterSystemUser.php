<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\SystemGateway\Webhosting;

use ParkManager\Application\Service\SystemGateway\SystemCommand;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class RegisterSystemUser extends SystemCommand
{
    public function __construct(SpaceId $id)
    {
        parent::__construct(['space_id' => $id]);
    }
}
