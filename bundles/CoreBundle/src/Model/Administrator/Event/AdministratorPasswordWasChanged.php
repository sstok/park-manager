<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Model\Administrator\Event;

use ParkManager\Bundle\CoreBundle\Model\Administrator\AdministratorId;

final class AdministratorPasswordWasChanged
{
    /**
     * READ-ONLY.
     *
     * @var AdministratorId
     */
    public $id;

    /**
     * READ-ONLY.
     *
     * @var string|null
     */
    public $newPassword;

    public function __construct(AdministratorId $id, ?string $newPassword)
    {
        $this->id = $id;
        $this->newPassword = $newPassword;
    }
}
