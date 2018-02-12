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

namespace ParkManager\Module\Webhosting\Model\Account\Event;

use ParkManager\Component\Model\Event\DomainEvent;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountId;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountOwner;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class WebhostingAccountOwnerWasSwitched extends DomainEvent
{
    private $id;
    private $oldOwner;
    private $newOwner;

    public function __construct(WebhostingAccountId $id, WebhostingAccountOwner $oldOwner, WebhostingAccountOwner $newOwner)
    {
        $this->newOwner = $newOwner;
        $this->oldOwner = $oldOwner;
        $this->id = $id;
    }

    public function id(): WebhostingAccountId
    {
        return $this->id;
    }

    public function oldOwner(): WebhostingAccountOwner
    {
        return $this->oldOwner;
    }

    public function newOwner(): WebhostingAccountOwner
    {
        return $this->newOwner;
    }
}
