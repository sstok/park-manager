<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\Webhosting\Domain\Account\Event;

use ParkManager\Component\SharedKernel\Event\DomainEvent;
use ParkManager\Component\SharedKernel\RootEntityOwner;
use ParkManager\Module\Webhosting\Domain\Account\WebhostingAccountId;

final class WebhostingAccountOwnerWasSwitched extends DomainEvent
{
    private $id;
    private $oldOwner;
    private $newOwner;

    public function __construct(WebhostingAccountId $id, RootEntityOwner $oldOwner, RootEntityOwner $newOwner)
    {
        $this->newOwner = $newOwner;
        $this->oldOwner = $oldOwner;
        $this->id = $id;
    }

    public function id(): WebhostingAccountId
    {
        return $this->id;
    }

    public function oldOwner(): RootEntityOwner
    {
        return $this->oldOwner;
    }

    public function newOwner(): RootEntityOwner
    {
        return $this->newOwner;
    }
}
