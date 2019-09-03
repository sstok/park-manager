<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Model\Administrator\Event;

use ParkManager\Bundle\CoreBundle\Model\Administrator\AdministratorId;

final class AdministratorNameWasChanged
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
     * @var string
     */
    public $name;

    public function __construct(AdministratorId $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }

    public function getId(): AdministratorId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
